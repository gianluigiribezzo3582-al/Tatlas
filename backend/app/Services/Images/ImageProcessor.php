<?php

namespace App\Services\Images;

use GdImage;
use InvalidArgumentException;

/**
 * Normalizes uploaded photos with GD: applies EXIF orientation, downsizes, and
 * re-encodes to JPEG. Re-encoding drops all metadata (GPS, device, timestamps),
 * which matters for privacy: portfolio photos are taken with phones.
 */
class ImageProcessor
{
    public const MAX_SIDE = 2048;

    public const THUMB_SIDE = 480;

    /**
     * @return array{image: string, thumbnail: string, width: int, height: int}
     */
    public function process(string $path): array
    {
        $bytes = @file_get_contents($path);
        $img = $bytes === false ? false : @imagecreatefromstring($bytes);
        if (! $img instanceof GdImage) {
            throw new InvalidArgumentException('Unsupported or corrupted image.');
        }

        $img = $this->orient($img, $bytes, $path);
        $main = $this->fit($img, self::MAX_SIDE);
        $thumb = $this->fit($img, self::THUMB_SIDE);

        return [
            'image' => $this->jpeg($main, 82),
            'thumbnail' => $this->jpeg($thumb, 78),
            'width' => imagesx($main),
            'height' => imagesy($main),
        ];
    }

    private function orient(GdImage $img, string $bytes, string $path): GdImage
    {
        if (! function_exists('exif_read_data') || ! str_starts_with($bytes, "\xFF\xD8")) {
            return $img;
        }
        $exif = @exif_read_data($path);
        $rotated = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => imagerotate($img, 180, 0),
            6 => imagerotate($img, -90, 0),
            8 => imagerotate($img, 90, 0),
            default => $img,
        };

        return $rotated instanceof GdImage ? $rotated : $img;
    }

    private function fit(GdImage $img, int $maxSide): GdImage
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $scale = min(1, $maxSide / max($w, $h));
        if ($scale === 1) {
            return $img;
        }
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $out = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $out;
    }

    private function jpeg(GdImage $img, int $quality): string
    {
        // Flatten transparency (PNG/WebP) on white.
        $canvas = imagecreatetruecolor(imagesx($img), imagesy($img));
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
        ob_start();
        imagejpeg($canvas, null, $quality);

        return (string) ob_get_clean();
    }
}

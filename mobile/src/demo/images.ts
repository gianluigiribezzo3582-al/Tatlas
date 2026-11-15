// Abstract placeholder images bundled with the app (no real tattoo photos).
// Metro resolves require() of an image to an asset id (number).
export const demoImages: number[] = [
  require('../../assets/demo/work-0.jpg'),
  require('../../assets/demo/work-1.jpg'),
  require('../../assets/demo/work-2.jpg'),
  require('../../assets/demo/work-3.jpg'),
  require('../../assets/demo/work-4.jpg'),
  require('../../assets/demo/work-5.jpg'),
  require('../../assets/demo/work-6.jpg'),
  require('../../assets/demo/work-7.jpg'),
];

export const DEMO_IMAGE_SCHEME = 'demo-image://';

/** expo-image source for a work URL: bundled module in demo mode, remote URI otherwise. */
export function imageSource(url: string): number | { uri: string } {
  if (url.startsWith(DEMO_IMAGE_SCHEME)) {
    return demoImages[Number(url.slice(DEMO_IMAGE_SCHEME.length))] ?? demoImages[0];
  }
  return { uri: url };
}

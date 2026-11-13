<?php

namespace Database\Seeders;

use App\Models\Style;
use Illuminate\Database\Seeder;

/**
 * Idempotent: upserts by slug, so it can run on every deploy.
 */
class StyleSeeder extends Seeder
{
    public function run(): void
    {
        $order = 0;
        foreach (require database_path('data/styles.php') as $node) {
            $this->upsert($node, null, $order++);
        }
    }

    /** @param array{slug: string, it: string, en: string, synonyms?: list<string>, children?: list<array<string, mixed>>} $node */
    private function upsert(array $node, ?int $parentId, int $order): void
    {
        $style = Style::updateOrCreate(['slug' => $node['slug']], [
            'parent_id' => $parentId,
            'name_it' => $node['it'],
            'name_en' => $node['en'],
            'synonyms' => array_values(array_unique(array_map('mb_strtolower', $node['synonyms'] ?? []))),
            'sort_order' => $order,
        ]);

        foreach ($node['children'] ?? [] as $i => $child) {
            $this->upsert($child, $style->id, $i);
        }
    }
}

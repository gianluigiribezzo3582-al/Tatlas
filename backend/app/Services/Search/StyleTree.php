<?php

namespace App\Services\Search;

use Illuminate\Support\Facades\DB;

class StyleTree
{
    /**
     * Ids of the style with this slug and all its descendants.
     *
     * @return list<int>
     */
    public static function idsWithDescendants(string $slug): array
    {
        $rows = DB::select(<<<'SQL'
            WITH RECURSIVE tree AS (
                SELECT id FROM styles WHERE slug = ?
                UNION ALL
                SELECT s.id FROM styles s JOIN tree t ON s.parent_id = t.id
            )
            SELECT id FROM tree
            SQL, [$slug]);

        return array_map(fn ($r) => (int) $r->id, $rows);
    }
}

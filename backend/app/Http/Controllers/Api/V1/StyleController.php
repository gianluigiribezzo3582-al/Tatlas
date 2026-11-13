<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\StyleResource;
use App\Models\Style;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StyleController extends Controller
{
    /** Taxonomy as a tree (root styles with their children). */
    public function index(): AnonymousResourceCollection
    {
        $roots = Style::whereNull('parent_id')->orderBy('sort_order')->with('children')->get();

        return StyleResource::collection($roots);
    }
}

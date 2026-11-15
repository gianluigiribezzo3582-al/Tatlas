<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArtistProfileRequest extends FormRequest
{
    public function rules(): array
    {
        $creating = $this->isMethod('post');
        $req = $creating ? 'required' : 'sometimes';

        return [
            'name' => [$req, 'string', 'min:2', 'max:80'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'instagram_url' => ['nullable', 'url:https', 'max:255', 'regex:#^https://(www\.)?instagram\.com/[A-Za-z0-9._]{1,30}/?$#'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'languages' => ['sometimes', 'array', 'max:8'],
            'languages.*' => ['string', 'size:2', 'alpha'],
            'accepts_walk_in' => ['sometimes', 'boolean'],
            'price_range' => ['nullable', 'integer', 'between:1,3'],
            'styles' => ['sometimes', 'array', 'max:'.config('tatlas.max_styles_per_artist')],
            'styles.*' => ['string', 'distinct', Rule::exists('styles', 'slug')],
        ];
    }

    public function messages(): array
    {
        return ['instagram_url.regex' => 'Use a profile URL like https://www.instagram.com/yourname'];
    }
}

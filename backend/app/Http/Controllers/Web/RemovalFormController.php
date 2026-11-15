<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RemovalRequest;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RemovalFormController extends Controller
{
    public function show(Request $request): View
    {
        $type = in_array($request->query('tipo'), ['studio', 'artist', 'work'], true) ? $request->query('tipo') : null;
        $id = $type ? (int) $request->query('id') : null;
        $class = $type ? Relation::getMorphedModel($type) : null;
        $subject = $class && $id ? $class::find($id) : null;

        return view('public.removal', [
            'type' => $subject ? $type : null,
            'id' => $subject?->getKey(),
            'subject' => $subject,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Honeypot: bots fill every field.
        if ($request->filled('website_url')) {
            return redirect()->route('public.removal')->with('sent', true);
        }

        $data = $request->validate([
            'requester_name' => ['required', 'string', 'max:120'],
            'requester_email' => ['required', 'email', 'max:255'],
            'subject_type' => ['nullable', Rule::in(['studio', 'artist', 'work'])],
            'subject_id' => ['nullable', 'integer'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        if (empty($data['subject_type'])) {
            $data['subject_type'] = $data['subject_id'] = null;
        }

        RemovalRequest::create($data);

        return redirect()->route('public.removal')->with('sent', true);
    }
}

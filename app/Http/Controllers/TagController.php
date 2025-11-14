<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use App\Facades\Sqids;
use App\Http\Requests\Tag\TagRequest;
use App\Models\Tag;
use Illuminate\Support\Facades\Redirect;

class TagController extends Controller
{
    public function index(): Response
    {
        $Tag = Tag::select([
            'id',
            'name',
            'severity',
            'created_at',
            'updated_at',
            'deleted_at',
        ])->orderBy('id')->get();

        $Tag = Sqids::rec_encode_ids_in_list($Tag);

        return Inertia::render('tag/Index', [
            'tag' => $Tag
        ]);
    }

    public function store(TagRequest $request): RedirectResponse
    {
        Tag::create($request->validated());
        return redirect()
            ->route('tag.index')
            ->with('success', 'Tag Success Add.');
    }

    public function destroy(string $encodedId): RedirectResponse
    {
        $id = Sqids::decode($encodedId);
        if (empty($id)) abort(404, 'ID Not Validate.');

        $Tag = Tag::findOrFail($id);
        $Tag->delete();

        return redirect()
            ->route('tag.index')
            ->with('success', 'Tag Deleted.');
    }
}

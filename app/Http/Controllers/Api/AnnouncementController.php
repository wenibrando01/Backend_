<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $announcements = Announcement::query()
            ->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $announcements]);
    }

    public function adminIndex(Request $request)
    {
        $query = Announcement::query()->orderByDesc('is_pinned')->orderByDesc('id');

        $search = $request->string('search')->trim();
        if ($search->isNotEmpty()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('message', 'like', '%' . $search . '%');
            });
        }

        $perPage = min(max((int) $request->get('per_page', 20), 1), 100);
        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'is_published' => ['nullable', 'boolean'],
            'is_pinned' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ]);

        $validated['is_published'] = (bool) ($validated['is_published'] ?? true);
        $validated['is_pinned'] = (bool) ($validated['is_pinned'] ?? false);
        $validated['published_at'] = $validated['published_at'] ?? now();
        $validated['created_by'] = $request->user()?->id;

        $announcement = Announcement::query()->create($validated);

        return response()->json($announcement, 201);
    }

    public function show(string $id)
    {
        return response()->json(Announcement::query()->findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $announcement = Announcement::query()->findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'message' => ['sometimes', 'string'],
            'is_published' => ['nullable', 'boolean'],
            'is_pinned' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ]);

        if (array_key_exists('is_published', $validated)) {
            $validated['is_published'] = (bool) $validated['is_published'];
        }
        if (array_key_exists('is_pinned', $validated)) {
            $validated['is_pinned'] = (bool) $validated['is_pinned'];
        }

        $announcement->update($validated);

        return response()->json($announcement);
    }

    public function destroy(string $id)
    {
        $announcement = Announcement::query()->findOrFail($id);
        $announcement->delete();

        return response()->json(['message' => 'Announcement deleted']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrivateMessage;
use Illuminate\Http\Request;

class PrivateMessageController extends Controller
{
    public function studentInbox(Request $request)
    {
        $studentId = $request->user()?->student_id;
        abort_if($studentId === null, 404, 'Student profile not linked to this account.');

        $messages = PrivateMessage::query()
            ->with(['sender:id,first_name,last_name,name,username,email,role'])
            ->where('recipient_student_id', $studentId)
            ->where(function ($q) {
                $q->whereHas('sender', fn ($sq) => $sq->where('role', 'admin'))
                    ->orWhereNull('sender_id');
            })
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $messages,
            'unread_count' => $messages->where('is_read', false)->count(),
        ]);
    }

    public function adminIndex(Request $request)
    {
        $query = PrivateMessage::query()
            ->with(['sender:id,first_name,last_name,name,username,email,role', 'recipientStudent:id,first_name,last_name,name'])
            ->orderByDesc('sent_at')
            ->orderByDesc('id');

        $studentId = (int) $request->get('student_id', 0);
        if ($studentId > 0) {
            $query->where('recipient_student_id', $studentId);
        }

        $perPage = min(max((int) $request->get('per_page', 20), 1), 100);
        return response()->json($query->paginate($perPage));
    }

    public function markStudentRead(Request $request)
    {
        $studentId = $request->user()?->student_id;
        abort_if($studentId === null, 404, 'Student profile not linked to this account.');

        $ids = collect($request->input('ids', []))
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        $query = PrivateMessage::query()
            ->where('recipient_student_id', $studentId)
            ->where('is_read', false);

        if ($ids->isNotEmpty()) {
            $query->whereIn('id', $ids->all());
        }

        $updated = $query->update(['is_read' => true]);

        return response()->json(['updated' => $updated]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipient_student_id' => ['required', 'exists:students,id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        $validated['sender_id'] = $request->user()?->id;
        $validated['sent_at'] = now();

        $message = PrivateMessage::query()->create($validated);

        return response()->json($message->load(['sender:id,first_name,last_name,name,username,email,role']), 201);
    }
}

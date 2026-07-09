<?php

namespace App\Http\Controllers;

use App\Models\StudentNote;
use App\Models\TeamInvitation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $email = strtolower($request->user()->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        $recentNotes = $request->user()->studentNotes()
            ->select([
                'id',
                'user_id',
                'student_note_group_id',
                'title',
                'body',
                'updated_at',
            ])
            ->with('group:id,name')
            ->latest('updated_at')
            ->latest('id')
            ->limit(3)
            ->get()
            ->map(fn (StudentNote $note) => [
                'id' => $note->id,
                'studentNoteGroupId' => $note->student_note_group_id,
                'groupName' => $note->group->name,
                'title' => $note->title,
                'bodyPreview' => $note->bodyPreview(),
                'updatedAt' => $note->updated_at?->toISOString(),
            ]);

        return Inertia::render('Dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'recentNotes' => $recentNotes,
        ]);
    }
}

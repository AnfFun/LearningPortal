<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentNoteRequest;
use App\Http\Requests\UpdateStudentNoteRequest;
use App\Models\StudentNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class StudentNoteController extends Controller
{
    /**
     * Display the student's notes panel.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $groups = $user->studentNoteGroups()
            ->withCount('notes')
            ->orderBy('name')
            ->get();

        $selectedNote = $request->integer('note')
            ? StudentNote::query()
                ->whereBelongsTo($user)
                ->find($request->integer('note'))
            : null;

        if ($selectedNote !== null && ! $groups->contains('id', $selectedNote->student_note_group_id)) {
            $selectedNote = null;
        }

        $selectedGroupId = $selectedNote?->student_note_group_id
            ?? ($request->integer('group') ?: $groups->first()?->id);

        if ($selectedGroupId !== null && ! $groups->contains('id', $selectedGroupId)) {
            $selectedGroupId = $groups->first()?->id;
        }

        $notes = $selectedGroupId === null
            ? collect()
            : StudentNote::query()
                ->whereBelongsTo($user)
                ->where('student_note_group_id', $selectedGroupId)
                ->latest('updated_at')
                ->get();

        return Inertia::render('student/Notes', [
            'groups' => $groups->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'notesCount' => $group->notes_count,
            ]),
            'notes' => $notes->map(fn (StudentNote $note) => $this->noteData($note)),
            'selectedGroupId' => $selectedGroupId,
            'selectedNoteId' => $selectedNote?->id,
        ]);
    }

    /**
     * Store a newly created note.
     */
    public function store(StoreStudentNoteRequest $request): JsonResponse|RedirectResponse
    {
        $note = $request->user()->studentNotes()->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'note' => $this->noteData($note),
            ], HttpResponse::HTTP_CREATED);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note created.')]);

        return to_route('notes.index', ['group' => $note->student_note_group_id]);
    }

    /**
     * Update the specified note.
     */
    public function update(UpdateStudentNoteRequest $request, string $currentTeam, StudentNote $studentNote): JsonResponse|RedirectResponse
    {
        $studentNote->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'note' => $this->noteData($studentNote),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note updated.')]);

        return to_route('notes.index', ['group' => $studentNote->student_note_group_id]);
    }

    /**
     * Delete the specified note.
     */
    public function destroy(Request $request, string $currentTeam, StudentNote $studentNote): RedirectResponse
    {
        abort_unless($studentNote->user_id === $request->user()->id, 403);

        $groupId = $studentNote->student_note_group_id;

        $studentNote->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note deleted.')]);

        return to_route('notes.index', ['group' => $groupId]);
    }

    /**
     * @return array{
     *     id: int,
     *     studentNoteGroupId: int,
     *     title: string,
     *     body: string,
     *     bodyPreview: string,
     *     createdAt: string|null,
     *     updatedAt: string|null
     * }
     */
    private function noteData(StudentNote $note): array
    {
        return [
            'id' => $note->id,
            'studentNoteGroupId' => $note->student_note_group_id,
            'title' => $note->title,
            'body' => $note->body,
            'bodyPreview' => $note->bodyPreview(),
            'createdAt' => $note->created_at?->toISOString(),
            'updatedAt' => $note->updated_at?->toISOString(),
        ];
    }
}

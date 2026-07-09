<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentNoteGroupRequest;
use App\Http\Requests\UpdateStudentNoteGroupRequest;
use App\Models\StudentNoteGroup;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class StudentNoteGroupController extends Controller
{
    /**
     * Store a newly created note group.
     */
    public function store(StoreStudentNoteGroupRequest $request): RedirectResponse
    {
        $group = $request->user()->studentNoteGroups()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note group created.')]);

        return to_route('notes.index', ['group' => $group->id]);
    }

    /**
     * Update the specified note group.
     */
    public function update(UpdateStudentNoteGroupRequest $request, string $currentTeam, StudentNoteGroup $studentNoteGroup): RedirectResponse
    {
        $studentNoteGroup->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note group updated.')]);

        return to_route('notes.index', ['group' => $studentNoteGroup->id]);
    }
}

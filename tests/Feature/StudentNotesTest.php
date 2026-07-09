<?php

use App\Models\StudentNote;
use App\Models\StudentNoteGroup;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function studentNotesRoute(string $name, User $user, array $parameters = []): string
{
    return route($name, [
        'current_team' => $user->currentTeam->slug,
        ...$parameters,
    ]);
}

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();

    $response = $this->get(studentNotesRoute('notes.index', $user));

    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the student notes page', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(studentNotesRoute('notes.index', $user));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/Notes')
            ->has('groups', 0)
            ->has('notes', 0)
            ->where('selectedGroupId', null)
            ->where('selectedNoteId', null),
        );
});

test('notes page selects an owned note from the query string', function () {
    $user = User::factory()->create();
    StudentNoteGroup::factory()->create([
        'user_id' => $user->id,
        'name' => 'First Group',
    ]);
    $selectedGroup = StudentNoteGroup::factory()->create([
        'user_id' => $user->id,
        'name' => 'Second Group',
    ]);
    $note = StudentNote::factory()->forGroup($selectedGroup)->create([
        'title' => 'Open from dashboard',
        'body' => 'Dashboard **details**.',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(studentNotesRoute('notes.index', $user, ['note' => $note->id]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/Notes')
            ->where('selectedGroupId', $selectedGroup->id)
            ->where('selectedNoteId', $note->id)
            ->has('notes', 1)
            ->where('notes.0.id', $note->id)
            ->where('notes.0.title', 'Open from dashboard')
            ->where('notes.0.body', 'Dashboard **details**.'),
        );
});

test('notes page ignores note query strings that do not belong to the user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $group = StudentNoteGroup::factory()->create([
        'user_id' => $user->id,
        'name' => 'My Group',
    ]);
    $otherGroup = StudentNoteGroup::factory()->create([
        'user_id' => $otherUser->id,
    ]);
    $otherNote = StudentNote::factory()->forGroup($otherGroup)->create();

    $response = $this
        ->actingAs($user)
        ->get(studentNotesRoute('notes.index', $user, ['note' => $otherNote->id]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/Notes')
            ->where('selectedGroupId', $group->id)
            ->where('selectedNoteId', null)
            ->has('notes', 0),
        );
});

test('users can create note groups', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(studentNotesRoute('note-groups.store', $user), [
            'name' => 'Laravel Basics',
        ]);

    $group = StudentNoteGroup::query()->where('name', 'Laravel Basics')->firstOrFail();

    $response->assertRedirect(studentNotesRoute('notes.index', $user, ['group' => $group->id]));

    $this->assertDatabaseHas('student_note_groups', [
        'user_id' => $user->id,
        'name' => 'Laravel Basics',
    ]);
});

test('users cannot create duplicate note group names', function () {
    $user = User::factory()->create();

    StudentNoteGroup::factory()->create([
        'user_id' => $user->id,
        'name' => 'Laravel Basics',
    ]);

    $response = $this
        ->actingAs($user)
        ->post(studentNotesRoute('note-groups.store', $user), [
            'name' => 'Laravel Basics',
        ]);

    $response->assertSessionHasErrors('name');

    expect(StudentNoteGroup::query()
        ->where('user_id', $user->id)
        ->where('name', 'Laravel Basics')
        ->count())->toBe(1);
});

test('users can rename their note groups', function () {
    $user = User::factory()->create();
    $group = StudentNoteGroup::factory()->create([
        'user_id' => $user->id,
        'name' => 'Old Name',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(studentNotesRoute('note-groups.update', $user, ['studentNoteGroup' => $group]), [
            'name' => 'New Name',
        ]);

    $response->assertRedirect(studentNotesRoute('notes.index', $user, ['group' => $group->id]));

    $this->assertDatabaseHas('student_note_groups', [
        'id' => $group->id,
        'name' => 'New Name',
    ]);
});

test('users cannot rename another users note group', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $group = StudentNoteGroup::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other Notes',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(studentNotesRoute('note-groups.update', $user, ['studentNoteGroup' => $group]), [
            'name' => 'Changed',
        ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('student_note_groups', [
        'id' => $group->id,
        'name' => 'Other Notes',
    ]);
});

test('users can create notes in their own groups', function () {
    $user = User::factory()->create();
    $group = StudentNoteGroup::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->post(studentNotesRoute('notes.store', $user), [
            'student_note_group_id' => $group->id,
            'title' => 'Collections',
            'body' => 'Practice map and filter.',
        ]);

    $response->assertRedirect(studentNotesRoute('notes.index', $user, ['group' => $group->id]));

    $this->assertDatabaseHas('student_notes', [
        'user_id' => $user->id,
        'student_note_group_id' => $group->id,
        'title' => 'Collections',
        'body' => 'Practice map and filter.',
    ]);
});

test('autosave can create markdown notes as json', function () {
    $user = User::factory()->create();
    $group = StudentNoteGroup::factory()->create(['user_id' => $user->id]);
    $body = "## Collections\n\nUse **map** and <script>alert(\"x\")</script> filter.";

    $response = $this
        ->actingAs($user)
        ->postJson(studentNotesRoute('notes.store', $user), [
            'student_note_group_id' => $group->id,
            'title' => 'Collections',
            'body' => $body,
        ]);

    $note = StudentNote::query()->firstOrFail();

    $response
        ->assertCreated()
        ->assertJsonPath('note.id', $note->id)
        ->assertJsonPath('note.studentNoteGroupId', $group->id)
        ->assertJsonPath('note.title', 'Collections')
        ->assertJsonPath('note.body', $body)
        ->assertJsonPath('note.bodyPreview', 'Collections Use map and alert("x") filter.')
        ->assertJsonStructure([
            'note' => ['createdAt', 'updatedAt'],
        ]);

    $this->assertDatabaseHas('student_notes', [
        'id' => $note->id,
        'body' => $body,
    ]);
});

test('autosave normalizes blank titles and allows empty note bodies', function () {
    $user = User::factory()->create();
    $group = StudentNoteGroup::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->postJson(studentNotesRoute('notes.store', $user), [
            'student_note_group_id' => $group->id,
            'title' => '   ',
            'body' => '',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('note.title', 'Untitled note')
        ->assertJsonPath('note.body', '')
        ->assertJsonPath('note.bodyPreview', '');

    $this->assertDatabaseHas('student_notes', [
        'user_id' => $user->id,
        'title' => 'Untitled note',
        'body' => '',
    ]);
});

test('autosave requires the note body field to be present', function () {
    $user = User::factory()->create();
    $group = StudentNoteGroup::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->postJson(studentNotesRoute('notes.store', $user), [
            'student_note_group_id' => $group->id,
            'title' => 'Missing body',
        ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('body');
});

test('autosave rejects note bodies longer than twenty thousand characters', function () {
    $user = User::factory()->create();
    $group = StudentNoteGroup::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->postJson(studentNotesRoute('notes.store', $user), [
            'student_note_group_id' => $group->id,
            'title' => 'Too long',
            'body' => str_repeat('a', 20_001),
        ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('body');

    $this->assertDatabaseCount('student_notes', 0);
});

test('users cannot create notes in another users group', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherGroup = StudentNoteGroup::factory()->create(['user_id' => $otherUser->id]);

    $response = $this
        ->actingAs($user)
        ->post(studentNotesRoute('notes.store', $user), [
            'student_note_group_id' => $otherGroup->id,
            'title' => 'Private',
            'body' => 'This should not be saved.',
        ]);

    $response->assertSessionHasErrors('student_note_group_id');

    $this->assertDatabaseMissing('student_notes', [
        'user_id' => $user->id,
        'student_note_group_id' => $otherGroup->id,
    ]);
});

test('users can update their own notes', function () {
    $user = User::factory()->create();
    $group = StudentNoteGroup::factory()->create(['user_id' => $user->id]);
    $note = StudentNote::factory()->forGroup($group)->create([
        'title' => 'Old title',
        'body' => 'Old body',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(studentNotesRoute('notes.update', $user, ['studentNote' => $note]), [
            'student_note_group_id' => $group->id,
            'title' => 'Updated title',
            'body' => 'Updated body',
        ]);

    $response->assertRedirect(studentNotesRoute('notes.index', $user, ['group' => $group->id]));

    $this->assertDatabaseHas('student_notes', [
        'id' => $note->id,
        'title' => 'Updated title',
        'body' => 'Updated body',
    ]);
});

test('autosave can update markdown notes as json', function () {
    $user = User::factory()->create();
    $group = StudentNoteGroup::factory()->create(['user_id' => $user->id]);
    $note = StudentNote::factory()->forGroup($group)->create();
    $body = "### Updated\n\n- First\n- Second";

    $response = $this
        ->actingAs($user)
        ->patchJson(studentNotesRoute('notes.update', $user, ['studentNote' => $note]), [
            'student_note_group_id' => $group->id,
            'title' => 'Updated note',
            'body' => $body,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('note.id', $note->id)
        ->assertJsonPath('note.body', $body)
        ->assertJsonPath('note.bodyPreview', 'Updated First Second');

    $this->assertDatabaseHas('student_notes', [
        'id' => $note->id,
        'title' => 'Updated note',
        'body' => $body,
    ]);
});

test('users cannot update another users notes', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherGroup = StudentNoteGroup::factory()->create(['user_id' => $otherUser->id]);
    $note = StudentNote::factory()->forGroup($otherGroup)->create([
        'title' => 'Private title',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(studentNotesRoute('notes.update', $user, ['studentNote' => $note]), [
            'student_note_group_id' => $otherGroup->id,
            'title' => 'Changed',
            'body' => 'Changed body',
        ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('student_notes', [
        'id' => $note->id,
        'title' => 'Private title',
    ]);
});

test('users can delete their own notes', function () {
    $user = User::factory()->create();
    $group = StudentNoteGroup::factory()->create(['user_id' => $user->id]);
    $note = StudentNote::factory()->forGroup($group)->create();

    $response = $this
        ->actingAs($user)
        ->delete(studentNotesRoute('notes.destroy', $user, ['studentNote' => $note]));

    $response->assertRedirect(studentNotesRoute('notes.index', $user, ['group' => $group->id]));

    $this->assertDatabaseMissing('student_notes', [
        'id' => $note->id,
    ]);
});

test('users cannot delete another users notes', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherGroup = StudentNoteGroup::factory()->create(['user_id' => $otherUser->id]);
    $note = StudentNote::factory()->forGroup($otherGroup)->create();

    $response = $this
        ->actingAs($user)
        ->delete(studentNotesRoute('notes.destroy', $user, ['studentNote' => $note]));

    $response->assertForbidden();

    $this->assertDatabaseHas('student_notes', [
        'id' => $note->id,
    ]);
});

test('notes page only includes the authenticated users groups and notes', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $group = StudentNoteGroup::factory()->create([
        'user_id' => $user->id,
        'name' => 'My Group',
    ]);
    $otherGroup = StudentNoteGroup::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other Group',
    ]);

    StudentNote::factory()->forGroup($group)->create([
        'title' => 'Visible Note',
        'body' => 'Visible **Body**',
    ]);
    StudentNote::factory()->forGroup($otherGroup)->create([
        'title' => 'Hidden Note',
        'body' => 'Hidden Body',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(studentNotesRoute('notes.index', $user, ['group' => $group->id]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/Notes')
            ->has('groups', 1)
            ->where('groups.0.name', 'My Group')
            ->where('groups.0.notesCount', 1)
            ->has('notes', 1)
            ->where('notes.0.title', 'Visible Note')
            ->where('notes.0.body', 'Visible **Body**')
            ->where('notes.0.bodyPreview', 'Visible Body')
            ->where('selectedGroupId', $group->id),
        );
});

<?php

use App\Enums\TeamRole;
use App\Models\StudentNote;
use App\Models\StudentNoteGroup;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', ['current_team' => $user->currentTeam->slug]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('recentNotes', 0),
        );
});

test('dashboard includes the three most recently updated notes for the authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $group = StudentNoteGroup::factory()->create([
        'user_id' => $user->id,
        'name' => 'Laravel Basics',
    ]);
    $otherGroup = StudentNoteGroup::factory()->create([
        'user_id' => $otherUser->id,
    ]);
    $latestTimestamp = now()->subMinute();

    $firstLatestNote = StudentNote::factory()->forGroup($group)->create([
        'title' => 'First latest note',
        'body' => 'First **details**.',
        'created_at' => $latestTimestamp,
        'updated_at' => $latestTimestamp,
    ]);
    $secondLatestNote = StudentNote::factory()->forGroup($group)->create([
        'title' => 'Second latest note',
        'body' => "## Second\n\nUse **map** and <script>alert(\"x\")</script> filter.",
        'created_at' => $latestTimestamp,
        'updated_at' => $latestTimestamp,
    ]);
    $thirdLatestNote = StudentNote::factory()->forGroup($group)->create([
        'title' => 'Third latest note',
        'updated_at' => now()->subMinutes(2),
    ]);
    StudentNote::factory()->forGroup($group)->create([
        'title' => 'Older note',
        'updated_at' => now()->subMinutes(3),
    ]);
    StudentNote::factory()->forGroup($otherGroup)->create([
        'title' => 'Other users newest note',
        'updated_at' => now(),
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', ['current_team' => $user->currentTeam->slug]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('recentNotes', 3)
            ->where('recentNotes.0.id', $secondLatestNote->id)
            ->where('recentNotes.0.studentNoteGroupId', $group->id)
            ->where('recentNotes.0.groupName', 'Laravel Basics')
            ->where('recentNotes.0.title', 'Second latest note')
            ->where('recentNotes.0.bodyPreview', 'Second Use map and alert("x") filter.')
            ->where('recentNotes.1.id', $firstLatestNote->id)
            ->where('recentNotes.2.id', $thirdLatestNote->id),
        );
});

test('dashboard includes pending invitations for the authenticated user', function () {
    $owner = User::factory()->create(['name' => 'Taylor Otwell']);
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create(['name' => 'Laravel Team']);

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('pendingInvitations', 1)
        ->where('pendingInvitations.0.code', $invitation->code)
        ->where('pendingInvitations.0.inviterName', 'Taylor Otwell')
        ->where('pendingInvitations.0.team.name', 'Laravel Team')
        ->where('pendingInvitations.0.team.slug', $team->slug)
        ->missing('pendingInvitations.0.teamName'),
    );
});

test('dashboard does not include accepted invitations', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    TeamInvitation::factory()->accepted()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('pendingInvitations', 0),
    );
});

test('dashboard excludes expired invitations without deleting them', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('pendingInvitations', 0),
    );

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
    ]);
});

test('dashboard does not include or delete other users invitations', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'someone@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('pendingInvitations', 0),
    );

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
    ]);
});

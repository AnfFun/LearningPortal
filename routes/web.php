<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentNoteController;
use App\Http\Controllers\StudentNoteGroupController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('notes', [StudentNoteController::class, 'index'])->name('notes.index');
        Route::post('note-groups', [StudentNoteGroupController::class, 'store'])->name('note-groups.store');
        Route::patch('note-groups/{studentNoteGroup}', [StudentNoteGroupController::class, 'update'])->name('note-groups.update');
        Route::post('notes', [StudentNoteController::class, 'store'])->name('notes.store');
        Route::patch('notes/{studentNote}', [StudentNoteController::class, 'update'])->name('notes.update');
        Route::delete('notes/{studentNote}', [StudentNoteController::class, 'destroy'])->name('notes.destroy');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';

<?php

namespace App\Models;

use Database\Factories\StudentNoteGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, StudentNote> $notes
 */
#[Fillable(['user_id', 'name'])]
class StudentNoteGroup extends Model
{
    /** @use HasFactory<StudentNoteGroupFactory> */
    use HasFactory;

    /**
     * Get the user that owns the note group.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the notes in this group.
     *
     * @return HasMany<StudentNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(StudentNote::class);
    }
}

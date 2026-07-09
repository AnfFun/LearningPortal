<?php

namespace App\Models;

use Database\Factories\StudentNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property int $student_note_group_id
 * @property string $title
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read StudentNoteGroup $group
 */
#[Fillable(['user_id', 'student_note_group_id', 'title', 'body'])]
class StudentNote extends Model
{
    /** @use HasFactory<StudentNoteFactory> */
    use HasFactory;

    /**
     * Get a safe plain-text preview of the Markdown body.
     */
    public function bodyPreview(): string
    {
        $html = Str::markdown($this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5);

        return Str::limit(Str::squish($text), 240);
    }

    /**
     * Get the user that owns the note.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the group that contains the note.
     *
     * @return BelongsTo<StudentNoteGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(StudentNoteGroup::class, 'student_note_group_id');
    }
}

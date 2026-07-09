<?php

namespace Database\Factories;

use App\Models\StudentNote;
use App\Models\StudentNoteGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentNote>
 */
class StudentNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'body' => fake()->paragraph(),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (StudentNote $note) {
            if ($note->student_note_group_id === null) {
                $group = StudentNoteGroup::factory()->create([
                    'user_id' => $note->user_id ?? User::factory()->create()->id,
                ]);

                $note->student_note_group_id = $group->id;
                $note->user_id = $group->user_id;

                return;
            }

            if ($note->user_id === null) {
                $group = StudentNoteGroup::query()->find($note->student_note_group_id);

                $note->user_id = $group?->user_id ?? User::factory()->create()->id;
            }
        });
    }

    /**
     * Indicate that the note belongs to a specific group.
     */
    public function forGroup(StudentNoteGroup $group): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $group->user_id,
            'student_note_group_id' => $group->id,
        ]);
    }
}

<?php

namespace App\Http\Requests;

use App\Models\StudentNoteGroup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentNoteGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $group = $this->route('studentNoteGroup');

        return $this->user() !== null
            && $group instanceof StudentNoteGroup
            && $group->user_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var StudentNoteGroup $group */
        $group = $this->route('studentNoteGroup');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('student_note_groups', 'name')
                    ->where(fn ($query) => $query->where('user_id', $this->user()?->id))
                    ->ignore($group->id),
            ],
        ];
    }
}

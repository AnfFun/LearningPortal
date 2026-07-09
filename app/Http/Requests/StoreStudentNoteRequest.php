<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentNoteRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $title = trim((string) $this->input('title'));

        $data = [
            'title' => $title !== '' ? $title : __('Untitled note'),
        ];

        if ($this->exists('body')) {
            $data['body'] = (string) $this->input('body');
        }

        $this->merge($data);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_note_group_id' => [
                'required',
                'integer',
                Rule::exists('student_note_groups', 'id')
                    ->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
            ],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['present', 'string', 'max:20000'],
        ];
    }
}

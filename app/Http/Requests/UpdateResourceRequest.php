<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResourceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // You might want to add specific authorization logic here
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string|max:1000',
            'attachments' => 'sometimes|array',
            'attachments.*' => 'file|max:10240', // 10MB max per file
            'remove_attachments' => 'sometimes|array',
            'remove_attachments.*' => 'integer|exists:attachments,id',
            // relational ids
            'course_id' => [
                'sometimes',
                'nullable',
                Rule::exists('courses', 'id')->where(function ($query) {
                    return $query->where('major_id', $this->input('major_id'));
                }),
            ],
            'major_id'  => 'sometimes|nullable|exists:majors,id',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'title.max' => 'The title may not be greater than 255 characters.',
            'description.max' => 'The description may not be greater than 1000 characters.',
            'attachments.array' => 'Attachments must be an array of files.',
            'attachments.*.file' => 'Each attachment must be a valid file.',
            'attachments.*.max' => 'Each attachment may not be greater than 10MB.',
            'remove_attachments.array' => 'Remove attachments must be an array of IDs.',
            'remove_attachments.*.integer' => 'Each attachment ID must be an integer.',
            'remove_attachments.*.exists' => 'One or more attachment IDs do not exist.',
        ];
    }
}
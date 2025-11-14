<?php

namespace App\Http\Requests;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResourceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,webp,jpeg,png,pdf,docx,txt,mp4,mov,avi,mkv,webm|max:204800', // 200MB max per attachment
            'poll' => 'nullable|array',
            'poll.question' => 'required_with:poll|string|max:255',
            'poll.options' => 'required_with:poll|array|min:1',
            'poll.options.*' => 'required|string|max:255',
            // new relational ids
            'course_id' => [
                'nullable',
                Rule::exists('courses', 'id')->where(function ($query) {
                    return $query->where('major_id', $this->input('major_id'));
                }),
            ],
            'major_id'  => 'required|exists:majors,id',
            'faculty_id'=> 'required|exists:faculties,id',
        ];
    }
    public function messages(): array
    {
        return [
            'attachments.*.max'   => 'Each attachment must not exceed 5MB.',
            'attachments.*.mimes' => 'Unsupported file type. Allowed types: jpg, jpeg, png, pdf, docx, txt, mp4, mov, avi, mkv, webm.',
            'attachments.*.uploaded' => 'The file could not be uploaded. It may exceed the server limit (check upload_max_filesize & post_max_size).',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors'  => $validator->errors(),
        ], 422));
    }
}

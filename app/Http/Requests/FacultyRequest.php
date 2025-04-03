<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FacultyRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Keep it true for now
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:faculties,name',
            'description' => 'nullable|string',
        ];
    }
}

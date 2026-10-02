<?php

namespace App\Http\Requests;

class UpdateCategoryRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['name' => ['sometimes', 'required', 'string', 'max:255'], 'description' => ['sometimes', 'nullable', 'string'], 'status' => ['sometimes', 'boolean']];
    }
}

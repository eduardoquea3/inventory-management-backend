<?php

namespace App\Http\Requests;

class UpdateProductRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['name' => ['sometimes', 'required', 'string', 'max:255'], 'description' => ['sometimes', 'nullable', 'string'], 'price' => ['sometimes', 'numeric', 'min:0'], 'stock' => ['sometimes', 'integer', 'min:0'], 'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'], 'status' => ['sometimes', 'boolean']];
    }
}

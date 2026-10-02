<?php

namespace App\Http\Requests;

class ProductIndexRequest extends PaginatedListRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'q' => ['sometimes', 'string', 'max:255'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'status' => ['sometimes', 'boolean'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'min:0'],
            'min_stock' => ['sometimes', 'integer', 'min:0'],
            'max_stock' => ['sometimes', 'integer', 'min:0'],
            'sort_by' => ['sometimes', 'in:created_at,name,price,stock'],
            'sort_direction' => ['sometimes', 'in:asc,desc'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('min_price') && $this->filled('max_price') && (float) $this->input('min_price') > (float) $this->input('max_price')) {
                $validator->errors()->add('min_price', 'The minimum price must be less than or equal to the maximum price.');
            }
            if ($this->filled('min_stock') && $this->filled('max_stock') && (int) $this->input('min_stock') > (int) $this->input('max_stock')) {
                $validator->errors()->add('min_stock', 'The minimum stock must be less than or equal to the maximum stock.');
            }
        });
    }
}

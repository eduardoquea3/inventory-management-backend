<?php

namespace App\Http\Requests;

class CategoryIndexRequest extends PaginatedListRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'q' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'boolean'],
            'sort_by' => ['sometimes', 'in:created_at,name'],
            'sort_direction' => ['sometimes', 'in:asc,desc'],
        ];
    }
}

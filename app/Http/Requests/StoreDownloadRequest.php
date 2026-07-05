<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDownloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:2048'],
            'format' => ['required', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:255'],
            'platform_slug' => ['nullable', 'string', 'max:50'],
        ];
    }
}

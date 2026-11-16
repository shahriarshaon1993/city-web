<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * SEO-TEC-010 robots.txt editor: plain text up to 5,000 characters; blank goes back to the default.
 */
class UpdateRobotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('seo.manage');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.max' => 'robots.txt can be up to 5,000 characters.',
        ];
    }
}

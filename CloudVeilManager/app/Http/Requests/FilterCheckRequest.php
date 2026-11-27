<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A filter check: one URL or several, an optional target (activation, user or
 * group; none means the default group), and whether bypass is on.
 */
class FilterCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'url' => ['required_without:urls', 'nullable', 'string', 'max:2048'],
            'urls' => ['required_without:url', 'nullable', 'array', 'max:'.config('filter_check.max_urls', 50)],
            'urls.*' => ['required', 'string', 'max:2048'],
            'activation_id' => ['nullable', 'integer'],
            'identifier' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer'],
            'email' => ['nullable', 'email'],
            'group_id' => ['nullable', 'integer'],
            'bypass_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function urls(): array
    {
        $urls = $this->filled('urls') ? $this->input('urls') : [$this->input('url')];

        return array_values(array_map('strval', $urls));
    }
}

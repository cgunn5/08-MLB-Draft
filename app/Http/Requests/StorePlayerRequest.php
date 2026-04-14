<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:128'],
            'last_name' => ['required', 'string', 'max:128'],
            'player_pool' => ['required', 'string', Rule::in(['ncaa', 'hs'])],
            'school' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:32'],
            'aggregate_rank' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'aggregate_score' => ['nullable', 'numeric'],
        ];
    }
}

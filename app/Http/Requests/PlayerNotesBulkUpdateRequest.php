<?php

namespace App\Http\Requests;

use App\Models\Player;
use App\Support\PlayerNoteFieldKeys;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlayerNotesBulkUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $grades = $this->input('grades', []);
        if (! is_array($grades)) {
            return;
        }
        foreach ($grades as $k => $v) {
            if ($v === '') {
                $grades[$k] = null;
            }
        }
        $this->merge(['grades' => $grades]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $pool = $this->string('player_pool')->toString();
        $allowed = PlayerNoteFieldKeys::forPool($pool);

        $rules = [
            'player_pool' => ['required', Rule::in(['ncaa', 'hs'])],
            'player_id' => ['required', 'integer', Rule::exists('players', 'id')],
            'values' => ['required', 'array'],
            'grades' => ['nullable', 'array'],
        ];

        foreach ($allowed as $field) {
            $rules['values.'.$field] = ['nullable', 'string'];
            $rules['grades.'.$field] = PlayerNoteFieldKeys::gradeValueValidationRules($field);
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->failed()) {
                return;
            }

            $player = Player::query()->find($this->integer('player_id'));
            if (! $player || $player->player_pool !== $this->string('player_pool')->toString()) {
                $validator->errors()->add('player_id', __('The player does not match this pool.'));

                return;
            }

            $allowed = PlayerNoteFieldKeys::forPool($player->player_pool);
            $values = $this->input('values', []);
            if (! is_array($values)) {
                return;
            }

            $unknown = array_diff(array_keys($values), $allowed);
            if ($unknown !== []) {
                $validator->errors()->add('values', __('Unknown note fields were submitted.'));
            }

            $missing = array_diff($allowed, array_keys($values));
            if ($missing !== []) {
                $validator->errors()->add('values', __('All note sections must be present.'));
            }
        });
    }
}

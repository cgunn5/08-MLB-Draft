<?php

namespace App\Http\Requests;

use App\Models\Player;
use App\Support\PlayerNoteFieldKeys;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlayerNoteSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'field' => ['required', 'string', Rule::in($allowed)],
        ];

        if ($this->isMethod('PATCH')) {
            $rules['value'] = ['nullable', 'string'];
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
                $validator->errors()->add('player_id', __('The player does not match this section.'));
            }
        });
    }
}

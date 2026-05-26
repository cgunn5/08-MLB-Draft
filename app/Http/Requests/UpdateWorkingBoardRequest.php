<?php

namespace App\Http\Requests;

use App\Models\WorkingBoardEntry;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkingBoardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'rounds' => ['required', 'array'],
        ];
        foreach (WorkingBoardEntry::ROUND_KEYS as $key) {
            $rules["rounds.$key"] = ['sometimes', 'array'];
            $rules["rounds.$key.*.player_id"] = [
                'required',
                'integer',
                Rule::exists('players', 'id')->where('player_pool', 'hs'),
            ];
            $rules["rounds.$key.*.confidence"] = [
                'nullable',
                'string',
                'max:32',
                Rule::in(WorkingBoardEntry::CONFIDENCE_OPTIONS),
            ];
            $rules["rounds.$key.*.risk"] = [
                'nullable',
                'string',
                'max:32',
                Rule::in(WorkingBoardEntry::RISK_OPTIONS),
            ];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            /** @var array<string, mixed>|null $rounds */
            $rounds = $this->input('rounds');
            if (! is_array($rounds)) {
                return;
            }
            $ids = [];
            foreach (WorkingBoardEntry::ROUND_KEYS as $rk) {
                $list = $rounds[$rk] ?? [];
                if (! is_array($list)) {
                    continue;
                }
                foreach ($list as $row) {
                    if (is_array($row) && isset($row['player_id'])) {
                        $ids[] = (int) $row['player_id'];
                    }
                }
            }
            if (count($ids) !== count(array_unique($ids))) {
                $validator->errors()->add('rounds', __('Each player may only appear once on the board.'));
            }
        });
    }
}

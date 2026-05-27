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
            'boards' => ['required', 'array'],
        ];

        foreach (WorkingBoardEntry::BOARD_TYPES as $boardType) {
            $rules["boards.$boardType"] = ['required', 'array'];
            $rules["boards.$boardType.rounds"] = ['required', 'array'];

            foreach (WorkingBoardEntry::ROUND_KEYS as $key) {
                $rules["boards.$boardType.rounds.$key"] = ['sometimes', 'array'];
                $rules["boards.$boardType.rounds.$key.*.player_id"] = [
                    'required',
                    'integer',
                    Rule::exists('players', 'id')->where(
                        fn ($query) => $this->playerPoolConstraint($query, $boardType),
                    ),
                ];
                $rules["boards.$boardType.rounds.$key.*.confidence"] = [
                    'nullable',
                    'string',
                    'max:32',
                    Rule::in(WorkingBoardEntry::CONFIDENCE_OPTIONS),
                ];
                $rules["boards.$boardType.rounds.$key.*.risk"] = [
                    'nullable',
                    'string',
                    'max:32',
                    Rule::in(WorkingBoardEntry::RISK_OPTIONS),
                ];
            }
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            /** @var array<string, mixed>|null $boards */
            $boards = $this->input('boards');
            if (! is_array($boards)) {
                return;
            }

            foreach (WorkingBoardEntry::BOARD_TYPES as $boardType) {
                $board = $boards[$boardType] ?? null;
                if (! is_array($board)) {
                    continue;
                }
                $rounds = $board['rounds'] ?? null;
                if (! is_array($rounds)) {
                    continue;
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
                    $validator->errors()->add(
                        "boards.$boardType.rounds",
                        __('Each player may only appear once on this board.'),
                    );
                }
            }
        });
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    private function playerPoolConstraint($query, string $boardType): void
    {
        match ($boardType) {
            WorkingBoardEntry::BOARD_HS => $query->where('player_pool', 'hs'),
            WorkingBoardEntry::BOARD_NCAA => $query->where('player_pool', 'ncaa'),
            default => $query->whereIn('player_pool', ['hs', 'ncaa']),
        };
    }
}

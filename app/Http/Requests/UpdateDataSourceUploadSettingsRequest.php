<?php

namespace App\Http\Requests;

use App\Models\DataSourceUpload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDataSourceUploadSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $upload = $this->route('dataSourceUpload');

        return $upload instanceof DataSourceUpload
            && $upload->user_id === $this->user()?->id;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'column_order' => ['sometimes', 'array'],
            'column_order.*' => ['integer', 'min:0'],
            'heat_rules' => ['sometimes', 'array'],
            'for_hs_ranger_traits' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var DataSourceUpload $upload */
            $upload = $this->route('dataSourceUpload');
            $n = count($upload->header_row);

            if ($this->has('column_order')) {
                /** @var list<int> $order */
                $order = $this->input('column_order');
                if (count($order) !== $n) {
                    $validator->errors()->add('column_order', __('Column order must include every column.'));

                    return;
                }
                $sorted = $order;
                sort($sorted);
                $expected = range(0, max(0, $n - 1));
                if ($sorted !== $expected) {
                    $validator->errors()->add('column_order', __('Column order must be a valid permutation.'));
                }
            }

            if ($this->has('heat_rules')) {
                /** @var array<string, mixed> $rules */
                $rules = $this->input('heat_rules');
                $allowed = array_map(static fn ($h) => (string) $h, $upload->header_row);
                foreach ($rules as $colName => $rule) {
                    if (! is_array($rule)) {
                        $validator->errors()->add('heat_rules', __('Invalid heat rule format.'));

                        return;
                    }
                    if (! in_array((string) $colName, $allowed, true)) {
                        $validator->errors()->add('heat_rules', __('Unknown column in heat rules.'));

                        return;
                    }
                }
            }
        });
    }
}

<?php

namespace App\Http\Requests;

use App\Models\DataSourceUpload;
use App\Support\HsRangerTraitsSheetLayout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            'hs_profile_feed_slots' => ['sometimes', 'array'],
            'hs_profile_feed_slots.*' => ['string', Rule::in(HsRangerTraitsSheetLayout::hsProfileSlotKeys())],
            'dataset_browse_settings' => ['sometimes', 'array'],
            'dataset_browse_settings.players' => ['sometimes', 'array'],
            'dataset_browse_settings.players.*' => ['string', 'max:500'],
            'dataset_browse_settings.column_thresholds' => ['sometimes', 'array'],
            'dataset_browse_settings.column_thresholds.*.col' => ['required', 'integer', 'min:0'],
            'dataset_browse_settings.column_thresholds.*.min' => ['sometimes', 'nullable', 'numeric'],
            'dataset_browse_settings.column_thresholds.*.max' => ['sometimes', 'nullable', 'numeric'],
            'dataset_browse_settings.group_column' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'dataset_browse_settings.group_value' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'dataset_browse_settings.heat_min_pa' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'dataset_browse_settings.heat_volume_header' => ['sometimes', 'nullable', 'string', 'max:200'],
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

            if ($this->has('dataset_browse_settings')) {
                /** @var array<string, mixed> $browse */
                $browse = $this->input('dataset_browse_settings', []);
                if (isset($browse['column_thresholds']) && is_array($browse['column_thresholds'])) {
                    foreach ($browse['column_thresholds'] as $item) {
                        if (! is_array($item)) {
                            $validator->errors()->add('dataset_browse_settings.column_thresholds', __('Invalid threshold entry.'));

                            return;
                        }
                        $col = $item['col'] ?? null;
                        if (! is_numeric($col) || (int) $col < 0 || (int) $col >= $n) {
                            $validator->errors()->add('dataset_browse_settings.column_thresholds', __('Invalid threshold column.'));

                            return;
                        }
                    }
                }
                if (array_key_exists('group_column', $browse) && $browse['group_column'] !== null) {
                    $g = (int) $browse['group_column'];
                    if ($g < 0 || $g >= $n) {
                        $validator->errors()->add('dataset_browse_settings.group_column', __('Invalid group column.'));

                        return;
                    }
                }
            }
        });
    }
}

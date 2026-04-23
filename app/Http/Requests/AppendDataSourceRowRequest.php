<?php

namespace App\Http\Requests;

use App\Models\DataSourceUpload;
use App\Support\DataSourceCsvHeaders;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AppendDataSourceRowRequest extends FormRequest
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
        /** @var DataSourceUpload $upload */
        $upload = $this->route('dataSourceUpload');
        $n = count($upload->header_row);

        return [
            'cells' => ['required', 'array', 'size:'.$n],
            'cells.*' => ['nullable', 'string', 'max:8192'],
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
            $cells = $this->input('cells', []);
            if (! is_array($cells)) {
                return;
            }

            $playerIdx = DataSourceCsvHeaders::playerColumnIndex($upload->header_row);
            $raw = $cells[$playerIdx] ?? '';
            if (trim((string) $raw) === '') {
                $validator->errors()->add(
                    'cells.'.$playerIdx,
                    __('Enter a player name in the player column.'),
                );
            }
        });
    }
}

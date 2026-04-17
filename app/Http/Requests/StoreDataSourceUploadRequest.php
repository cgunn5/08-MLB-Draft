<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDataSourceUploadRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $file = $this->file('file');
            if ($file === null || ! $file->isValid()) {
                return;
            }

            $handle = fopen($file->getRealPath(), 'r');
            if ($handle === false) {
                $validator->errors()->add('file', __('Could not read the file.'));

                return;
            }

            try {
                $header = fgetcsv($handle);
            } finally {
                fclose($handle);
            }

            if ($header === false) {
                $validator->errors()->add('file', __('The file must be a CSV with a header row.'));

                return;
            }

            $trimmed = array_map(static fn ($c) => is_string($c) ? trim($c) : '', $header);
            if ($trimmed === [] || (count($trimmed) === 1 && $trimmed[0] === '')) {
                $validator->errors()->add('file', __('The file must be a CSV with a header row.'));
            }
        });
    }
}

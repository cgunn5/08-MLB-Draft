<?php

namespace App\Http\Requests;

use App\Models\DataSourceUpload;
use Illuminate\Foundation\Http\FormRequest;

class DataSourceRowUpdateRequest extends FormRequest
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
            'player' => ['required', 'string', 'max:512'],
        ];
    }
}

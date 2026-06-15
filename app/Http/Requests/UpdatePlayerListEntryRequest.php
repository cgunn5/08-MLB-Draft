<?php

namespace App\Http\Requests;

use App\Support\PlayerListSourceRanksInput;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlayerListEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageApplicationData() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:128'],
            'last_name' => ['required', 'string', 'max:128'],
        ] + PlayerListSourceRanksInput::validationRules();
    }
}

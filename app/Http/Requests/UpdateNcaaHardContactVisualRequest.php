<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNcaaHardContactVisualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plate_heatmap' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'zone_pitch_map' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->hasFile('plate_heatmap') && ! $this->hasFile('zone_pitch_map')) {
                $validator->errors()->add('plate_heatmap', __('Upload at least one image.'));
            }
        });
    }
}

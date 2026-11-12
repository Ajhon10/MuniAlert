<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('barangay'))) {
            $normalize = fn ($name) => strtolower(rtrim(trim(Str::ascii($name)), '.'));
            foreach (config('municipality.barangays') as $name) {
                if ($normalize($this->input('barangay')) === $normalize($name)) {
                    $this->merge(['barangay' => $name]);
                    break;
                }
            }
        }
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'], 'title' => ['required', 'string', 'min:5', 'max:120'], 'description' => ['required', 'string', 'min:15', 'max:2000'],
            'address' => ['required', 'string', 'min:3', 'max:200'], 'barangay' => ['required', 'string', Rule::in(config('municipality.barangays'))],
            'selected_latitude' => ['required', 'numeric', 'between:-90,90'], 'selected_longitude' => ['required', 'numeric', 'between:-180,180'],
            'gps_latitude' => ['nullable', 'required_with:gps_longitude,gps_accuracy', 'required_if:location_source,gps,gps_adjusted', 'numeric', 'between:-90,90'],
            'gps_longitude' => ['nullable', 'required_with:gps_latitude,gps_accuracy', 'required_if:location_source,gps,gps_adjusted', 'numeric', 'between:-180,180'],
            'gps_accuracy' => ['nullable', 'required_with:gps_latitude,gps_longitude', 'required_if:location_source,gps,gps_adjusted', 'numeric', 'between:0,100000'],
            'location_source' => ['required', Rule::in(['gps', 'gps_adjusted', 'manual'])], 'location_confirmed' => ['required', 'accepted'],
            'severity' => ['required', Rule::in(['low', 'moderate', 'high', 'critical'])],
            'occurred_at' => ['nullable', 'date', 'before_or_equal:now'],
            'injuries' => ['sometimes', Rule::in(['unknown', 'yes', 'no'])],
            'people_affected' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'assistance_needed' => ['sometimes', 'boolean'],
            'photos' => ['required', 'array', 'min:1', 'max:3'], 'photos.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:1024', 'dimensions:max_width=1920,max_height=1920'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($this->location_source === 'gps' && is_numeric($this->gps_latitude) && is_numeric($this->gps_longitude) && (abs((float) $this->selected_latitude - (float) $this->gps_latitude) > 0.000001 || abs((float) $this->selected_longitude - (float) $this->gps_longitude) > 0.000001)) {
                $validator->errors()->add('location_source', 'Use gps_adjusted when the selected pin differs from the phone GPS.');
            }
            if ($this->location_source === 'manual' && ($this->filled('gps_latitude') || $this->filled('gps_longitude'))) {
                $validator->errors()->add('location_source', 'Use gps_adjusted when keeping GPS evidence for an adjusted pin.');
            }
        }];
    }

    public function messages(): array
    {
        return ['barangay.in' => 'Choose one of the 13 barangays served by Basista Municipal Hall.',
            'category_id.required' => 'Choose the type of hazard.', 'photos.required' => 'Add at least one safe incident photo.',
            'selected_latitude.required' => 'Choose the hazard pin on the map or use your current location.',
            'selected_longitude.required' => 'Choose the hazard pin on the map or use your current location.',
            'location_confirmed.accepted' => 'Check the hazard pin and confirm its location before sending.'];
    }
}

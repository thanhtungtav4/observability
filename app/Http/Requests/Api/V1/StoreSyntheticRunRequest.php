<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSyntheticRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'siteKey' => ['required', 'string', 'max:255'],
            'environment' => ['required', 'string', Rule::in(config('performance-hub.environments'))],
            'buildId' => ['required', 'string', 'max:255'],
            'occurredAt' => ['required', 'date'],
            'pageUrl' => ['required', 'url'],
            'pagePath' => ['required', 'string'],
            'pageGroupKey' => ['required', 'string', 'max:255'],
            'devicePreset' => ['required', 'string', Rule::in(config('performance-hub.synthetic_device_presets'))],
            'performanceScore' => ['required', 'numeric', 'min:0', 'max:100'],
            'accessibilityScore' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bestPracticesScore' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'seoScore' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fcpMs' => ['nullable', 'integer', 'min:0'],
            'lcpMs' => ['nullable', 'integer', 'min:0'],
            'tbtMs' => ['nullable', 'integer', 'min:0'],
            'clsScore' => ['nullable', 'numeric', 'min:0'],
            'speedIndexMs' => ['nullable', 'integer', 'min:0'],
            'inpMs' => ['nullable', 'integer', 'min:0'],
            'screenshotUrl' => ['nullable', 'url'],
            'traceUrl' => ['nullable', 'url'],
            'reportUrl' => ['nullable', 'url'],
            'opportunities' => ['present', 'array'],
            'diagnostics' => ['present', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'siteKey.required' => 'A site key is required to store a synthetic run.',
            'devicePreset.in' => 'Synthetic runs currently support only mobile and desktop presets.',
            'performanceScore.required' => 'A Lighthouse performance score is required.',
        ];
    }
}

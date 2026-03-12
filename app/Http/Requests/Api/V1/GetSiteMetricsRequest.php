<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetSiteMetricsRequest extends FormRequest
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
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'metric' => ['nullable', 'string', Rule::in(config('performance-hub.metric_names'))],
            'deviceClass' => ['nullable', 'string', Rule::in(config('performance-hub.device_classes'))],
            'pageGroupKey' => ['nullable', 'string', 'max:255'],
        ];
    }
}

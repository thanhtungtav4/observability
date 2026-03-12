<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetSiteCausesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'environment' => ['nullable', 'string', Rule::in(config('performance-hub.environments'))],
            'metric' => ['nullable', 'string', Rule::in(config('performance-hub.metric_names'))],
            'deviceClass' => ['nullable', 'string', Rule::in(config('performance-hub.device_classes'))],
            'pageGroupKey' => ['nullable', 'string', 'max:255'],
        ];
    }
}

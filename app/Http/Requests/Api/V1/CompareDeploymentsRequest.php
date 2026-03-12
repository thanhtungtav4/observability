<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompareDeploymentsRequest extends FormRequest
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
            'currentDeploymentId' => ['required', 'uuid', Rule::exists('deployments', 'id')],
            'baselineDeploymentId' => ['nullable', 'uuid', Rule::exists('deployments', 'id')],
            'deviceClass' => ['nullable', 'string', Rule::in(config('performance-hub.device_classes'))],
        ];
    }
}

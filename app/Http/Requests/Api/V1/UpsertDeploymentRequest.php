<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertDeploymentRequest extends FormRequest
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
            'releaseVersion' => ['nullable', 'string', 'max:255'],
            'gitRef' => ['nullable', 'string', 'max:255'],
            'gitCommitSha' => ['nullable', 'string', 'max:255'],
            'deployedAt' => ['required', 'date'],
            'actorName' => ['nullable', 'string', 'max:255'],
            'ciSource' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'siteKey.required' => 'A site key is required to register a deployment.',
            'buildId.required' => 'A build ID is required to register a deployment.',
            'deployedAt.required' => 'A deployment timestamp is required.',
        ];
    }
}

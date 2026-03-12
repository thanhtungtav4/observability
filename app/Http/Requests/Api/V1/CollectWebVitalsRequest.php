<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CollectWebVitalsRequest extends FormRequest
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
            'events' => ['required', 'array', 'min:1', 'max:50'],
            'events.*.eventId' => ['required', 'uuid'],
            'events.*.occurredAt' => ['required', 'date'],
            'events.*.metricName' => ['required', 'string', Rule::in(config('performance-hub.metric_names'))],
            'events.*.metricUnit' => ['required', 'string', Rule::in(config('performance-hub.metric_units'))],
            'events.*.metricValue' => ['required', 'numeric', 'min:0'],
            'events.*.deltaValue' => ['nullable', 'numeric', 'min:0'],
            'events.*.rating' => ['required', 'string', Rule::in(config('performance-hub.ratings'))],
            'events.*.url' => ['required', 'url'],
            'events.*.path' => ['required', 'string'],
            'events.*.pageTitle' => ['nullable', 'string', 'max:255'],
            'events.*.pageGroupKey' => ['required', 'string', 'max:255'],
            'events.*.deviceClass' => ['required', 'string', Rule::in(config('performance-hub.device_classes'))],
            'events.*.navigationType' => ['nullable', 'string', 'max:255'],
            'events.*.effectiveConnectionType' => ['nullable', 'string', 'max:255'],
            'events.*.browserName' => ['nullable', 'string', 'max:255'],
            'events.*.browserVersion' => ['nullable', 'string', 'max:255'],
            'events.*.osName' => ['nullable', 'string', 'max:255'],
            'events.*.countryCode' => ['nullable', 'string', 'size:2'],
            'events.*.roundTripTimeMs' => ['nullable', 'integer', 'min:0'],
            'events.*.downlinkMbps' => ['nullable', 'numeric', 'min:0'],
            'events.*.sessionId' => ['nullable', 'uuid'],
            'events.*.pageViewId' => ['nullable', 'uuid'],
            'events.*.visitorHash' => ['nullable', 'string', 'max:255'],
            'events.*.release' => ['required', 'array'],
            'events.*.release.buildId' => ['required', 'string', 'max:255'],
            'events.*.release.releaseVersion' => ['nullable', 'string', 'max:255'],
            'events.*.release.gitRef' => ['nullable', 'string', 'max:255'],
            'events.*.release.gitCommitSha' => ['nullable', 'string', 'max:255'],
            'events.*.release.deployedAt' => ['nullable', 'date'],
            'events.*.attribution' => ['present', 'array'],
            'events.*.tags' => ['present', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'siteKey.required' => 'A site key is required for Web Vitals ingestion.',
            'environment.in' => 'The provided environment is not supported.',
            'events.required' => 'At least one Web Vitals event must be provided.',
            'events.max' => 'A single ingestion request may include at most 50 events.',
            'events.*.release.buildId.required' => 'Each event must include a release build ID.',
        ];
    }
}

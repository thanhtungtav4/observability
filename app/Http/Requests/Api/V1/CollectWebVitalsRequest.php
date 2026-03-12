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
            'events.*.correlationId' => ['nullable', 'string', 'max:255'],
            'events.*.traceId' => ['nullable', 'string', 'max:255'],
            'events.*.visitorHash' => ['nullable', 'string', 'max:255'],
            'events.*.release' => ['required', 'array:buildId,releaseVersion,gitRef,gitCommitSha,deployedAt'],
            'events.*.release.buildId' => ['required', 'string', 'max:255'],
            'events.*.release.releaseVersion' => ['nullable', 'string', 'max:255'],
            'events.*.release.gitRef' => ['nullable', 'string', 'max:255'],
            'events.*.release.gitCommitSha' => ['nullable', 'string', 'max:255'],
            'events.*.release.deployedAt' => ['nullable', 'date'],
            'events.*.attribution' => ['present', 'array'],
            'events.*.tags' => ['present', 'array'],
            'events.*.context' => ['sometimes', 'array:collectorVersion,runtime,loadState,visibilityState,networkState,cpuClass,memoryClass,hydrationPhase,routeTransitionType,serverTiming,apiRequestKeys'],
            'events.*.context.collectorVersion' => ['nullable', 'string', 'max:50'],
            'events.*.context.runtime' => ['nullable', 'string', 'max:50'],
            'events.*.context.loadState' => ['nullable', 'string', 'max:50'],
            'events.*.context.visibilityState' => ['nullable', 'string', 'max:50'],
            'events.*.context.networkState' => ['nullable', 'string', 'max:50'],
            'events.*.context.cpuClass' => ['nullable', 'string', 'max:50'],
            'events.*.context.memoryClass' => ['nullable', 'string', 'max:50'],
            'events.*.context.hydrationPhase' => ['nullable', 'string', 'max:50'],
            'events.*.context.routeTransitionType' => ['nullable', 'string', 'max:50'],
            'events.*.context.serverTiming' => ['sometimes', 'array', 'max:20'],
            'events.*.context.serverTiming.*' => ['array:name,duration,description'],
            'events.*.context.serverTiming.*.name' => ['required', 'string', 'max:100'],
            'events.*.context.serverTiming.*.duration' => ['nullable', 'numeric', 'min:0'],
            'events.*.context.serverTiming.*.description' => ['nullable', 'string', 'max:255'],
            'events.*.context.apiRequestKeys' => ['sometimes', 'array', 'max:20'],
            'events.*.context.apiRequestKeys.*' => ['string', 'max:255'],
            'events.*.resources' => ['sometimes', 'array', 'max:25'],
            'events.*.resources.*' => ['array:url,resourceType,initiatorType,durationMs,transferSize,decodedBodySize,cacheState,priority,renderBlocking,isLcpCandidate'],
            'events.*.resources.*.url' => ['required', 'string', 'max:2048'],
            'events.*.resources.*.resourceType' => ['nullable', 'string', 'max:50'],
            'events.*.resources.*.initiatorType' => ['nullable', 'string', 'max:50'],
            'events.*.resources.*.durationMs' => ['nullable', 'numeric', 'min:0'],
            'events.*.resources.*.transferSize' => ['nullable', 'integer', 'min:0'],
            'events.*.resources.*.decodedBodySize' => ['nullable', 'integer', 'min:0'],
            'events.*.resources.*.cacheState' => ['nullable', 'string', 'max:50'],
            'events.*.resources.*.priority' => ['nullable', 'string', 'max:50'],
            'events.*.resources.*.renderBlocking' => ['sometimes', 'boolean'],
            'events.*.resources.*.isLcpCandidate' => ['sometimes', 'boolean'],
            'events.*.longTasks' => ['sometimes', 'array', 'max:25'],
            'events.*.longTasks.*' => ['array:name,scriptUrl,invokerType,containerSelector,startTimeMs,durationMs,blockingDurationMs'],
            'events.*.longTasks.*.name' => ['nullable', 'string', 'max:100'],
            'events.*.longTasks.*.scriptUrl' => ['nullable', 'string', 'max:2048'],
            'events.*.longTasks.*.invokerType' => ['nullable', 'string', 'max:50'],
            'events.*.longTasks.*.containerSelector' => ['nullable', 'string', 'max:255'],
            'events.*.longTasks.*.startTimeMs' => ['nullable', 'numeric', 'min:0'],
            'events.*.longTasks.*.durationMs' => ['required', 'numeric', 'min:0'],
            'events.*.longTasks.*.blockingDurationMs' => ['nullable', 'numeric', 'min:0'],
            'events.*.errors' => ['sometimes', 'array', 'max:25'],
            'events.*.errors.*' => ['array:name,message,sourceUrl,lineNumber,columnNumber,handled,stack'],
            'events.*.errors.*.name' => ['nullable', 'string', 'max:100'],
            'events.*.errors.*.message' => ['required', 'string', 'max:2000'],
            'events.*.errors.*.sourceUrl' => ['nullable', 'string', 'max:2048'],
            'events.*.errors.*.lineNumber' => ['nullable', 'integer', 'min:1'],
            'events.*.errors.*.columnNumber' => ['nullable', 'integer', 'min:1'],
            'events.*.errors.*.handled' => ['sometimes', 'boolean'],
            'events.*.errors.*.stack' => ['nullable', 'string', 'max:12000'],
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
            'events.*.resources.max' => 'Attach at most 25 resource timing rows per event.',
            'events.*.longTasks.max' => 'Attach at most 25 long task rows per event.',
            'events.*.errors.max' => 'Attach at most 25 JavaScript errors per event.',
        ];
    }
}

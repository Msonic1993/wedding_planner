<?php

namespace App\Http\Requests\Api\V1\Notification;

use App\Domain\Notification\Models\PushDevice;
use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class RegisterPushDeviceRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Only Expo tokens are accepted; raw FCM/APNs tokens would be rejected by the Expo Push API.
            'token' => ['required', 'string', 'max:255', 'regex:/^Expo(nent)?PushToken\[[A-Za-z0-9_-]+\]$/'],
            'platform' => ['required', 'string', Rule::in(PushDevice::PLATFORMS)],
        ];
    }
}

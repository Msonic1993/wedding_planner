<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Controllers\BaseController;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

abstract class ApiRequest extends FormRequest
{
    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => __('app.errors.validation.required'),
            'string' => __('app.errors.validation.string'),
            'email' => __('app.errors.validation.email'),
            'max' => __('app.errors.validation.max'),
            'unique' => __('app.errors.validation.unique'),
            'confirmed' => __('app.errors.validation.confirmed'),
            'current_password' => __('app.errors.validation.current_password'),
            'in' => __('app.errors.validation.in'),
            'date' => __('app.errors.validation.date'),
            'after_or_equal' => __('app.errors.validation.after_or_equal'),
            'numeric' => __('app.errors.validation.numeric'),
            'decimal' => __('app.errors.validation.decimal'),
            'integer' => __('app.errors.validation.integer'),
            'min' => __('app.errors.validation.min'),
            'image' => __('app.errors.validation.image'),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(BaseController::errorResponse(
            __('app.errors.validation.message'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $validator->errors(),
        ));
    }
}

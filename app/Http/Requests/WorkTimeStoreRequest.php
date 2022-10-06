<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkTimeStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'seller_id' => 'required|integer',
            'work_times' => 'required|array',
            'work_times.*.day_of_week' => 'required|integer|between:0,6',
            'work_times.*.start_time' => 'required|date_format:H:i',
            'work_times.*.end_time' => 'required|date_format:H:i',
        ];
    }

    /**
     * Return errors in case os failure
     */
    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}

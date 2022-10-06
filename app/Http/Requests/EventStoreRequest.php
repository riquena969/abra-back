<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventStoreRequest extends FormRequest
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
            'title' => 'required|string',
            'notes' => 'nullable|string',
            'all_day' => 'required|boolean',
            'start' => 'required|date',
            'end' => 'required|date',
            'seller_id' => 'nullable|integer',
            'repeat' => 'required|boolean',
            'repeat_type' => 'nullable|required_if:repeat,true|in:day,week,month,year',
            'repeat_interval' => 'nullable|required_if:repeat,true|integer|min:1',
            'repeat_count' => 'nullable|integer|min:1',
            'repeat_until' => 'nullable|date',
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

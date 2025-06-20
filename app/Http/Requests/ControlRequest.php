<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ControlRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'control_name' => 'required|string',
            'control_number' => 'required|unique:controls,control_number',
            'control_description' => 'required|string',
            'due_date' => 'required|date',
            'pp_reference' => 'required|string',
            'preventive_detective' => 'nullable|numeric',
            'user_id' => 'required|numeric|exists:users,id',
            'department' => 'required|string',
            'key_type_id' => 'nullable|numeric|exists:key_types,id',
            'reminder_id' => 'nullable|numeric|exists:reminders,id',
            'status_id' => 'nullable|numeric|exists:statuses,id',
        ];
    }

    public function messages()
    {
        return [
            'control_name.required' => 'Please enter control name',
            'control_number.unique' => 'Control Number must be unique',
            'control_description.required' => 'Please enter control description',
            'due_date.required' => 'Enter a valid date',
            'pp_reference.required' => 'Please enter PP Reference',
            'user_id.required' => 'Please select a process owner',
            'department.required' => 'Please select a department',
        ];
    }
}

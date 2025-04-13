<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
{
    return [
        'email' => [
            'required',
            'string',
            'email',
            'max:255',
            'lowercase', // Ensure this is a valid rule in your validation library
            Rule::unique(User::class)->ignore($this->user()->id),
        ],
        'first_name' => ['required', 'string', 'max:255'],
        'last_name' => ['required', 'string', 'max:255'],
        'contact_number' => ['required', 'string', 'min:10'],
        'birthdate' => ['required', 'date'],
        'sex' => ['required', 'in:male,female,other'],
        'position' => ['nullable', 'string', 'max:255'],
        'municipality_id' => ['nullable', 'exists:municipalities,id'],
        'barangay_id' => ['nullable', 'exists:barangays,id'],
    ];
}
}

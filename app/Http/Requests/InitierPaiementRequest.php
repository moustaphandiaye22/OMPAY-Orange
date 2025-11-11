<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitierPaiementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'idMarchand' => 'required|string',
            'montant' => 'required|numeric|min:50|max:500000',
            'devise' => 'required|string|size:3',
            'modePaiement' => 'sometimes|in:qr_code,code',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'idMarchand.required' => 'L\'ID du marchand est requis.',
            'idMarchand.string' => 'L\'ID du marchand doit être une chaîne de caractères.',
            'montant.required' => 'Le montant est requis.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant minimum est de 50 XOF.',
            'montant.max' => 'Le montant maximum est de 500 000 XOF.',
            'devise.required' => 'La devise est requise.',
            'devise.string' => 'La devise doit être une chaîne de caractères.',
            'devise.size' => 'La devise doit contenir exactement 3 caractères.',
            'modePaiement.in' => 'Le mode de paiement doit être qr_code ou code.',
        ];
    }
}
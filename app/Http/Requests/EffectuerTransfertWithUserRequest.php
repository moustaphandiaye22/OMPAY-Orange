<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EffectuerTransfertWithUserRequest extends FormRequest
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
            'telephoneDestinataire' => 'required|string|regex:/^\+221[0-9]{9}$/',
            'montant' => 'required|numeric|min:100|max:500000',
            'devise' => 'required|string|in:XOF,FCFA',
            'note' => 'sometimes|string|max:255',
            'codePin' => 'required|string|size:4|regex:/^[0-9]+$/',
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
            'telephoneDestinataire.required' => 'Le numéro de téléphone du destinataire est requis.',
            'telephoneDestinataire.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'telephoneDestinataire.regex' => 'Le numéro de téléphone doit être au format +221XXXXXXXXX.',
            'montant.required' => 'Le montant est requis.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant minimum est de 100 XOF.',
            'montant.max' => 'Le montant maximum est de 500 000 XOF.',
            'devise.required' => 'La devise est requise.',
            'devise.in' => 'La devise doit être XOF ou FCFA.',
            'note.string' => 'La note doit être une chaîne de caractères.',
            'note.max' => 'La note ne peut pas dépasser 255 caractères.',
            'codePin.required' => 'Le code PIN est requis.',
            'codePin.string' => 'Le code PIN doit être une chaîne de caractères.',
            'codePin.size' => 'Le code PIN doit contenir exactement 4 caractères.',
            'codePin.regex' => 'Le code PIN ne doit contenir que des chiffres.',
        ];
    }
}
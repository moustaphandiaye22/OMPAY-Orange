<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EffectuerPaiementRequest extends FormRequest
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
            'montant' => 'required|numeric|min:50|max:500000',
            'devise' => 'required|string|size:3',
            'codePin' => 'required|string|size:4|regex:/^[0-9]+$/',
            'modePaiement' => 'required|in:qr_code,code,telephone',
            'donneesQR' => 'required_if:modePaiement,qr_code|string',
            'code' => 'required_if:modePaiement,code|string',
            'numeroTelephone' => 'required_if:modePaiement,telephone|string|regex:/^\+221[0-9]{9}$/',
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
            'idUtilisateur.required' => 'L\'ID de l\'utilisateur est requis.',
            'idUtilisateur.string' => 'L\'ID de l\'utilisateur doit être une chaîne de caractères.',
            'idUtilisateur.uuid' => 'L\'ID de l\'utilisateur doit être un UUID valide.',
            'montant.required' => 'Le montant est requis.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant minimum est de 50 XOF.',
            'montant.max' => 'Le montant maximum est de 500 000 XOF.',
            'devise.required' => 'La devise est requise.',
            'devise.string' => 'La devise doit être une chaîne de caractères.',
            'devise.size' => 'La devise doit contenir exactement 3 caractères.',
            'codePin.required' => 'Le code PIN est requis.',
            'codePin.string' => 'Le code PIN doit être une chaîne de caractères.',
            'codePin.size' => 'Le code PIN doit contenir exactement 4 caractères.',
            'codePin.regex' => 'Le code PIN ne doit contenir que des chiffres.',
            'modePaiement.required' => 'Le mode de paiement est requis.',
            'modePaiement.in' => 'Le mode de paiement doit être qr_code, code ou telephone.',
            'donneesQR.required_if' => 'Les données QR sont requises pour le mode de paiement QR code.',
            'donneesQR.string' => 'Les données QR doivent être une chaîne de caractères.',
            'code.required_if' => 'Le code de paiement est requis pour le mode de paiement code.',
            'code.string' => 'Le code de paiement doit être une chaîne de caractères.',
            'numeroTelephone.required_if' => 'Le numéro de téléphone est requis pour le mode de paiement téléphone.',
            'numeroTelephone.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'numeroTelephone.regex' => 'Le numéro de téléphone doit être au format +221XXXXXXXXX.',
        ];
    }
}
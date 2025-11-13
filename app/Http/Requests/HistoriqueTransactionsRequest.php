<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HistoriqueTransactionsRequest extends FormRequest
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
            'page' => 'sometimes|integer|min:1',
            'limite' => 'sometimes|integer|min:1|max:100',
            'type' => 'sometimes|string|in:transfert,paiement,tous',
            'dateDebut' => 'sometimes|date|date_format:Y-m-d',
            'dateFin' => 'sometimes|date|date_format:Y-m-d|after_or_equal:dateDebut',
            'statut' => 'sometimes|string|in:en_attente,termine,echoue,annule',
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
            'page.integer' => 'Le numéro de page doit être un entier.',
            'page.min' => 'Le numéro de page doit être au minimum 1.',
            'limite.integer' => 'La limite doit être un entier.',
            'limite.min' => 'La limite doit être au minimum 1.',
            'limite.max' => 'La limite ne peut pas dépasser 100.',
            'type.in' => 'Le type doit être transfert, paiement ou tous.',
            'dateDebut.date' => 'La date de début doit être une date valide.',
            'dateDebut.date_format' => 'La date de début doit être au format Y-m-d.',
            'dateFin.date' => 'La date de fin doit être une date valide.',
            'dateFin.date_format' => 'La date de fin doit être au format Y-m-d.',
            'dateFin.after_or_equal' => 'La date de fin doit être après ou égale à la date de début.',
            'statut.in' => 'Le statut doit être en_attente, termine, echoue ou annule.',
        ];
    }
}
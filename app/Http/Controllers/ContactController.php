<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    // 5.1 Lister les Contacts
    public function listerContacts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recherche' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
            'limite' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Paramètres invalides',
                    'details' => $validator->errors()
                ]
            ], 422);
        }

        $utilisateur = $request->user();
        $page = $request->get('page', 1);
        $limite = $request->get('limite', 50);

        $query = Contact::where('idUtilisateur', $utilisateur->idUtilisateur)
                        ->with('destinataire:idUtilisateur,numeroTelephone,prenom,nom');

        if ($request->has('recherche')) {
            $recherche = $request->recherche;
            $query->where(function ($q) use ($recherche) {
                $q->where('nom', 'like', "%{$recherche}%")
                  ->orWhereHas('destinataire', function ($subQ) use ($recherche) {
                      $subQ->where('numeroTelephone', 'like', "%{$recherche}%");
                  });
            });
        }

        $contacts = $query->paginate($limite, ['*'], 'page', $page);

        $data = $contacts->map(function ($contact) {
            $destinataire = $contact->destinataire;
            $nombreTransactions = 0; // Calculer depuis les transactions
            $derniereTransaction = null; // Récupérer la dernière transaction

            return [
                'idContact' => $contact->idContact,
                'nom' => $contact->nom,
                'numeroTelephone' => $destinataire ? $destinataire->numeroTelephone : null,
                'photo' => $contact->photo,
                'nombreTransactions' => $nombreTransactions,
                'derniereTransaction' => $derniereTransaction,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'contacts' => $data,
                'pagination' => [
                    'pageActuelle' => $contacts->currentPage(),
                    'totalPages' => $contacts->lastPage(),
                    'totalElements' => $contacts->total(),
                ]
            ]
        ]);
    }

    // 5.2 Ajouter un Contact
    public function ajouterContact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|min:2|max:100',
            'numeroTelephone' => 'required|string|regex:/^\+221[0-9]{9}$/',
            'photo' => 'nullable|string', // Base64 ou URL
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Données invalides',
                    'details' => $validator->errors()
                ]
            ], 422);
        }

        $utilisateur = $request->user();

        // Vérifier si le destinataire existe
        $destinataire = Utilisateur::where('numeroTelephone', $request->numeroTelephone)->first();
        if (!$destinataire) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CONTACT_003',
                    'message' => 'Numéro invalide'
                ]
            ], 422);
        }

        // Vérifier si le contact existe déjà
        $contactExistant = Contact::where('idUtilisateur', $utilisateur->idUtilisateur)
                                  ->where('idDestinataire', $destinataire->idUtilisateur)
                                  ->first();

        if ($contactExistant) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CONTACT_002',
                    'message' => 'Contact déjà existant'
                ]
            ], 409);
        }

        $idContact = 'cnt_' . Str::random(10);

        $contact = Contact::create([
            'idContact' => $idContact,
            'idUtilisateur' => $utilisateur->idUtilisateur,
            'idDestinataire' => $destinataire->idUtilisateur,
            'nom' => $request->nom,
            'photo' => $request->photo,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'idContact' => $contact->idContact,
                'nom' => $contact->nom,
                'numeroTelephone' => $destinataire->numeroTelephone,
                'photo' => $contact->photo,
                'nombreTransactions' => 0,
            ],
            'message' => 'Contact ajouté avec succès'
        ], 201);
    }
}

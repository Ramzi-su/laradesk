<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'facturation' => ['Facturation', 'Factures, paiements, remboursements et abonnements.'],
            'technique' => ['Technique', 'Bugs, erreurs et problèmes de fonctionnement.'],
            'compte' => ['Compte', 'Connexion, mot de passe et informations personnelles.'],
            'livraison' => ['Livraison', 'Suivi de commande, retards et colis endommagés.'],
            'autre' => ['Autre', 'Toute autre demande.'],
        ];

        foreach ($categories as $slug => [$name, $description]) {
            Category::updateOrCreate(['slug' => $slug], compact('name', 'description'));
        }
    }
}

# Poste Santé Boulal — CaissePro Santé

Application de gestion de caisse et pharmacie pour le Poste de Santé de Boulal.

Développée avec Laravel, Vue.js et Inertia.js.

## Stack technique

- **Backend** : Laravel
- **Frontend** : Vue.js 3 + Inertia.js
- **Styles** : Tailwind CSS
- **Base de données** : MySQL
- **Impression** : Tickets thermiques 80mm (Epson TM-T20III, GIGA360)

## Fonctionnalités

- Gestion de caisse Accueil (ouverture, vente de tickets, dépenses, clôture)
- Gestion de caisse Pharmacie (vente de médicaments, gestion de stock)
- Rapports PDF de clôture avec hash d'intégrité
- Journal d'audit complet
- Rôles : Caissier, Pharmacien, Administrateur
- **Version mobile** : PWA avec impression Bluetooth (GIGA360)

## Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build

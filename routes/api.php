<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aucune route API pour le moment.
| L'impression Bluetooth mobile passe par le pont JavaScript
| window.AndroidPrinter injecte par l'app Flutter WebView
| (voir resources/js/Pages/Tickets/Recu.vue).
|
| NOTE : ce fichier n'est pas charge par bootstrap/app.php.
| Si des routes API deviennent necessaires, ajouter
| api: __DIR__.'/../routes/api.php' dans withRouting().
|
*/

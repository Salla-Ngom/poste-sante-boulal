<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();

            // Session expirée (jeton CSRF) : retour à la page avec message,
            // plutôt qu'une page d'erreur bloquante
            if ($status === 419) {
                return back()->with('error', 'Votre session a expiré. Veuillez réessayer.');
            }

            $statutsPersonnalises = [403, 404, 405, 429, 500, 503];

            if (in_array($status, $statutsPersonnalises)) {
                // En mode debug, conserver la page détaillée de Laravel
                // pour les erreurs serveur (indispensable au développement)
                if (in_array($status, [500, 503]) && config('app.debug')) {
                    return $response;
                }

                // Afficher le message métier uniquement pour les 403 :
                // ce sont les messages français des abort() du projet
                // (ex. "Cette action est réservée au superadmin.")
                $message = ($status === 403 && $exception->getMessage() !== '')
                    ? $exception->getMessage()
                    : null;

                return Inertia::render('Errors/Show', [
                    'status' => $status,
                    'message' => $message,
                ])->toResponse($request)->setStatusCode($status);
            }

            return $response;
        });
    })->create();

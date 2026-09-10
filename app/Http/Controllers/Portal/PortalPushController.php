<?php

namespace App\Http\Controllers\Portal;

use App\Models\Caregiver;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Guarda/borra la suscripción de notificaciones push del navegador del actor
 * autenticado (patient o caregiver) — lo que la Push API del navegador entrega al
 * llamar pushManager.subscribe()/unsubscribe(). No depende de tener un caso activo:
 * es una preferencia de la cuenta, igual que el modo fácil.
 */
class PortalPushController
{
    public function publicKey(): JsonResponse
    {
        return response()->json(['publicKey' => config('services.webpush.public_key')]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $actor = $this->actor();
        abort_unless($actor, 403);

        $data = $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $actor->pushSubscriptions()->updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => 'aesgcm',
            ],
        );

        return response()->json(['status' => 'ok']);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $actor = $this->actor();
        abort_unless($actor, 403);

        $data = $request->validate(['endpoint' => 'required|string']);

        $actor->pushSubscriptions()->where('endpoint', $data['endpoint'])->delete();

        return response()->json(['status' => 'ok']);
    }

    private function actor(): Patient|Caregiver|null
    {
        return Auth::guard('patient')->user() ?? Auth::guard('caregiver')->user();
    }
}

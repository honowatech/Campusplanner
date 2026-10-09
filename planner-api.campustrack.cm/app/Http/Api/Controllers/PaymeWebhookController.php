<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Webhook PayMe (public, hors auth). L'idempotence est garantie par le hash du
 * payload (`payment_webhooks.hash` UNIQUE).
 *
 * ⚠️ La validation de signature/hash exacte de PayMe reste à confirmer (voir
 * §10 du plan) ; à renforcer dès réception de la spec.
 */
class PaymeWebhookController extends Controller
{
    public function callback(Request $request): JsonResponse
    {
        app(PaymentService::class)->processCallback($request->all());

        return response()->json(['status' => 'ok']);
    }
}

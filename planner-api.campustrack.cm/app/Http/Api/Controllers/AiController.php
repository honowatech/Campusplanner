<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\HttpResponses;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Proxy serveur pour l'API Gemini.
 *
 * La clé API ne quitte jamais le serveur : le frontend appelle cet endpoint
 * authentifié, qui seul dialogue avec Google.
 */
class AiController extends Controller
{
    use HttpResponses;

    /**
     * Modèles autorisés — liste blanche stricte pour éviter qu'un client
     * n'utilise ce proxy comme un accès libre à n'importe quel modèle.
     */
    private const ALLOWED_MODELS = [
        'gemini-2.5-flash',
    ];

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:20000',
            'system_instruction' => 'nullable|string|max:2000',
            'model' => ['nullable', 'string', 'in:'.implode(',', self::ALLOWED_MODELS)],
            'response_json' => 'nullable|boolean',
        ]);

        $apiKey = config('services.gemini.key');
        if (empty($apiKey)) {
            return $this->error(
                null,
                "Le service IA n'est pas configuré sur le serveur.",
                503
            );
        }

        $model = $validated['model'] ?? 'gemini-2.5-flash';

        $generationConfig = [
            // Réponses rapides : pas de raisonnement étendu pour ces usages
            'thinkingConfig' => ['thinkingBudget' => 0],
        ];
        if ($validated['response_json'] ?? false) {
            $generationConfig['responseMimeType'] = 'application/json';
        }

        $payload = [
            'contents' => [
                ['parts' => [['text' => $validated['prompt']]]],
            ],
            'generationConfig' => $generationConfig,
        ];

        if (! empty($validated['system_instruction'])) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $validated['system_instruction']]],
            ];
        }

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $apiKey])
                ->timeout(30)
                ->post(
                    rtrim(config('services.gemini.base_url'), '/')
                        ."/models/{$model}:generateContent",
                    $payload
                );
        } catch (ConnectionException $e) {
            return $this->error(null, 'Service IA indisponible.', 502);
        }

        if ($response->serverError() || $response->clientError()) {
            return $this->error(null, 'Erreur du service IA.', 502);
        }

        $text = collect($response->json('candidates.0.content.parts') ?? [])
            ->implode('text');

        if (trim((string) $text) === '') {
            return $this->error(null, 'Réponse vide du service IA.', 502);
        }

        return $this->success(['text' => $text], 'Réponse générée', 200);
    }
}

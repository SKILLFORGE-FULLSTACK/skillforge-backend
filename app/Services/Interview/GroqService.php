<?php

namespace App\Services\Interview;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqService
{
    private string $apiKey;

    private string $model;

    private string $baseUrl;

    private string $sttModel;

    private string $ttsModel;

    private string $ttsVoice;

    private string $ttsFormat;

    public function __construct()
    {
        $this->apiKey = config('services.groq.api_key');
        $this->model = config('services.groq.model', 'llama-3.3-70b-versatile');
        $this->baseUrl = 'https://api.groq.com/openai/v1';
        $this->sttModel = config('services.groq.stt_model', 'whisper-large-v3');
        $this->ttsModel = config('services.groq.tts_model', 'playai-tts');
        $this->ttsVoice = config('services.groq.tts_voice', 'Fritz-PlayAI');
        $this->ttsFormat = config('services.groq.tts_format', 'wav');
    }

    public function ttsFormat(): string
    {
        return $this->ttsFormat;
    }

    public function chat(array $messages, int $maxTokens = 1000): string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->retry(2, 500)->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
                'temperature' => 0.7,
            ]);

            if ($response->failed()) {
                Log::error('Groq API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return 'Je rencontre un problème technique. Veuillez réessayer.';
            }

            return $response->json('choices.0.message.content', '');
        } catch (\Exception $e) {
            Log::error('Groq Service Exception', ['error' => $e->getMessage()]);

            return 'Service IA temporairement indisponible.';
        }
    }

    public function generateQuestion(string $type, string $difficulty, string $stack = ''): array
    {
        $stackContext = $stack ? "en utilisant {$stack}" : '';

        $prompt = <<<PROMPT
Tu es un interviewer technique senior dans une entreprise tech.
Génère UNE question d'entretien technique de type "{$type}" niveau "{$difficulty}" {$stackContext}.

Réponds UNIQUEMENT en JSON valide avec cette structure exacte :
{
  "title": "Titre court de la question",
  "description": "Énoncé complet et clair de la question",
  "constraints": "Contraintes techniques si applicable (null sinon)",
  "examples": [{"input": "exemple entrée", "output": "exemple sortie", "explanation": "explication"}],
  "hints": ["indice 1 progressif", "indice 2 plus précis", "indice 3 quasi-réponse"],
  "expected_concepts": ["concept 1", "concept 2"]
}
PROMPT;

        $response = $this->chat([
            ['role' => 'user', 'content' => $prompt],
        ], 800);

        try {
            $cleaned = preg_replace('/```json|```/', '', $response);

            return json_decode(trim($cleaned), true) ?? $this->fallbackQuestion($type);
        } catch (\Exception $e) {
            return $this->fallbackQuestion($type);
        }
    }

    public function analyzeResponse(
        string $question,
        string $answer,
        ?string $code,
        ?string $executionResult,
        string $difficulty
    ): array {
        $codeSection = $code
            ? "\n\nCode soumis:\n```\n{$code}\n```\nRésultat exécution: {$executionResult}"
            : '';

        $prompt = <<<PROMPT
Tu es un interviewer technique senior. Analyse cette réponse d'entretien.

Question: {$question}

Réponse du candidat: {$answer}{$codeSection}

Évalue et réponds UNIQUEMENT en JSON valide :
{
  "score": 75,
  "verdict": "good|excellent|needs_improvement|poor",
  "strengths": ["point fort 1", "point fort 2"],
  "weaknesses": ["point faible 1", "point faible 2"],
  "feedback": "Feedback narratif détaillé et constructif en français (3-5 phrases)",
  "model_answer": "Ce que la réponse idéale aurait dû contenir",
  "next_hint": "Conseil pour s'améliorer sur ce type de question"
}

Le score doit refléter : correction (40%), efficacité (30%), clarté (30%).
Niveau attendu: {$difficulty}
PROMPT;

        $response = $this->chat([
            ['role' => 'user', 'content' => $prompt],
        ], 1200);

        try {
            $cleaned = preg_replace('/```json|```/', '', $response);

            return json_decode(trim($cleaned), true) ?? $this->fallbackAnalysis();
        } catch (\Exception $e) {
            return $this->fallbackAnalysis();
        }
    }

    public function generateHint(string $question, string $partialAnswer, int $hintNumber): string
    {
        $prompt = <<<PROMPT
Tu es un interviewer bienveillant. Le candidat est bloqué sur cette question :
"{$question}"

Sa réponse partielle : "{$partialAnswer}"

Donne-lui l'indice numéro {$hintNumber} (plus précis si numéro élevé).
Réponds en 2-3 phrases maximum, en français, sans donner la réponse complète.
PROMPT;

        return $this->chat([
            ['role' => 'user', 'content' => $prompt],
        ], 200);
    }

    public function generateSessionReport(array $sessionData): array
    {
        $questionsJson = json_encode($sessionData['questions'], JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
Tu es un coach technique. Génère un rapport d'entretien complet basé sur ces données :

Type: {$sessionData['type']}
Difficulté: {$sessionData['difficulty']}
Score global: {$sessionData['score']}/100
Questions et réponses: {$questionsJson}

Réponds UNIQUEMENT en JSON valide :
{
  "overall_feedback": "Feedback global narratif (4-6 phrases)",
  "strengths": ["force majeure 1", "force majeure 2", "force majeure 3"],
  "improvement_areas": ["domaine à améliorer 1", "domaine à améliorer 2"],
  "resources": [
    {"title": "Titre ressource", "type": "article|video|doc", "topic": "sujet concerné"}
  ],
  "readiness_level": "not_ready|almost_ready|ready|senior_ready",
  "estimated_weeks_to_improve": 4
}
PROMPT;

        $response = $this->chat([
            ['role' => 'user', 'content' => $prompt],
        ], 1500);

        try {
            $cleaned = preg_replace('/```json|```/', '', $response);

            return json_decode(trim($cleaned), true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    // ─── AUDIO : SPEECH-TO-TEXT (Whisper) ──────────────────────────

    public function transcribeAudio(string $absolutePath, string $language = 'fr', string $filename = 'audio.wav'): string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->attach('file', file_get_contents($absolutePath), $filename)
                ->retry(2, 500)
                ->post("{$this->baseUrl}/audio/transcriptions", [
                    'model' => $this->sttModel,
                    'language' => $language,
                    'response_format' => 'json',
                ]);

            if ($response->failed()) {
                Log::error('Groq STT error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return '';
            }

            return trim($response->json('text', ''));
        } catch (\Exception $e) {
            Log::error('Groq STT Exception', ['error' => $e->getMessage()]);

            return '';
        }
    }

    // ─── AUDIO : TEXT-TO-SPEECH (PlayAI) ────────────────────────────

    public function synthesizeSpeech(string $text): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->retry(2, 500)->post("{$this->baseUrl}/audio/speech", [
                'model' => $this->ttsModel,
                'voice' => $this->ttsVoice,
                'input' => $text,
                'response_format' => $this->ttsFormat,
            ]);

            if ($response->failed()) {
                Log::error('Groq TTS error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->body();
        } catch (\Exception $e) {
            Log::error('Groq TTS Exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    // ─── ENTRETIEN VOCAL CONVERSATIONNEL ────────────────────────────

    public function generateInterviewOpening(string $context, string $language = 'fr'): string
    {
        $langInstruction = $language === 'en' ? 'in English' : 'en français';

        $prompt = <<<PROMPT
Tu es un recruteur technique qui mène un entretien vocal en direct avec un candidat.
{$context}

Lance l'entretien {$langInstruction} : présente-toi en une phrase, puis pose ta première question
(une question d'accroche naturelle, pas une liste). Reste conversationnel, chaleureux mais professionnel.
Réponds uniquement avec le texte que tu vas dire à voix haute, sans guillemets ni formatage.
PROMPT;

        return trim($this->chat([
            ['role' => 'user', 'content' => $prompt],
        ], 300));
    }

    public function generateInterviewReply(
        string $context,
        array $history,
        int $turnNumber,
        int $maxTurns,
        string $language = 'fr'
    ): array {
        $langInstruction = $language === 'en' ? 'in English' : 'en français';

        $system = <<<PROMPT
Tu es un recruteur technique qui mène un entretien vocal en direct avec un candidat, {$langInstruction}.
{$context}

Règles :
- Pose une seule question ou remarque à la fois, de façon naturelle et conversationnelle (pas de liste à puces).
- Rebondis sur ce que dit le candidat, creuse ses réponses avant de changer de sujet.
- C'est le tour {$turnNumber} sur un maximum de {$maxTurns}.
- Si on approche ou dépasse le tour {$maxTurns}, amène l'entretien vers sa conclusion naturellement (remercie le candidat, annonce la fin).

Réponds UNIQUEMENT en JSON valide :
{"reply": "ce que tu dis au candidat", "should_end": false}
PROMPT;

        $messages = array_merge(
            [['role' => 'system', 'content' => $system]],
            $history,
        );

        $response = $this->chat($messages, 400);

        try {
            $cleaned = preg_replace('/```json|```/', '', $response);
            $data = json_decode(trim($cleaned), true);

            return [
                'reply' => $data['reply'] ?? 'Merci pour votre réponse. Pouvez-vous m\'en dire plus ?',
                'should_end' => (bool) ($data['should_end'] ?? false),
            ];
        } catch (\Exception $e) {
            return [
                'reply' => 'Merci pour votre réponse. Pouvez-vous m\'en dire plus ?',
                'should_end' => false,
            ];
        }
    }

    public function generateVoiceInterviewReport(string $context, string $transcript, string $language = 'fr'): array
    {
        $prompt = <<<PROMPT
Tu es un coach de carrière technique. Voici le contexte d'un entretien vocal et sa transcription complète.

{$context}

Transcription :
{$transcript}

Évalue la performance du candidat et réponds UNIQUEMENT en JSON valide :
{
  "score": 75,
  "overall_feedback": "Feedback global narratif (4-6 phrases)",
  "strengths": ["force 1", "force 2"],
  "improvement_areas": ["axe 1", "axe 2"],
  "resources": [{"title": "Titre ressource", "type": "article|video|doc", "topic": "sujet concerné"}],
  "readiness_level": "not_ready|almost_ready|ready|senior_ready",
  "estimated_weeks_to_improve": 4
}
PROMPT;

        $response = $this->chat([
            ['role' => 'user', 'content' => $prompt],
        ], 1500);

        try {
            $cleaned = preg_replace('/```json|```/', '', $response);

            return json_decode(trim($cleaned), true) ?? $this->fallbackVoiceReport();
        } catch (\Exception $e) {
            return $this->fallbackVoiceReport();
        }
    }

    private function fallbackVoiceReport(): array
    {
        return [
            'score' => 50,
            'overall_feedback' => 'Rapport non disponible. Veuillez réessayer.',
            'strengths' => [],
            'improvement_areas' => [],
            'resources' => [],
            'readiness_level' => 'not_ready',
            'estimated_weeks_to_improve' => null,
        ];
    }

    private function fallbackQuestion(string $type): array
    {
        return [
            'title' => 'Question technique',
            'description' => 'Expliquez votre approche pour résoudre un problème d\'optimisation de base de données.',
            'constraints' => null,
            'examples' => [],
            'hints' => ['Pensez aux index', 'Considérez la pagination'],
            'expected_concepts' => ['index', 'query optimization'],
        ];
    }

    private function fallbackAnalysis(): array
    {
        return [
            'score' => 50,
            'verdict' => 'needs_improvement',
            'strengths' => ['Vous avez tenté de répondre'],
            'weaknesses' => ['Réponse incomplète'],
            'feedback' => 'Analyse non disponible. Veuillez réessayer.',
            'model_answer' => 'Non disponible',
            'next_hint' => 'Continuez à pratiquer',
        ];
    }
}

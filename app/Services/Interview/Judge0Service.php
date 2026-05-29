<?php

namespace App\Services\Interview;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Judge0Service
{
    private string $baseUrl;

    // Map langages → Judge0 language IDs
    private array $languageMap = [
        'php'        => 68,
        'javascript' => 63,
        'typescript' => 74,
        'python'     => 71,
        'java'       => 62,
        'go'         => 60,
        'rust'       => 73,
        'cpp'        => 54,
        'c'          => 50,
        'bash'       => 46,
    ];

    public function __construct()
    {
        $this->baseUrl = config('services.judge0.url', 'http://localhost:2358');
    }

    public function execute(string $code, string $language, ?string $stdin = null): array
    {
        $languageId = $this->languageMap[strtolower($language)] ?? 63;

        try {
            // Soumettre le code
            $submission = Http::timeout(10)
                ->post("{$this->baseUrl}/submissions", [
                    'source_code' => base64_encode($code),
                    'language_id' => $languageId,
                    'stdin'       => $stdin ? base64_encode($stdin) : null,
                    'cpu_time_limit'    => 5,
                    'memory_limit'      => 128000,
                    'enable_per_process_and_thread_time_limit' => false,
                ]);

            if ($submission->failed()) {
                return $this->errorResult('Erreur de soumission du code.');
            }

            $token = $submission->json('token');

            // Attendre le résultat (polling)
            return $this->pollResult($token);
        } catch (\Exception $e) {
            Log::error('Judge0 error', ['error' => $e->getMessage()]);
            return $this->errorResult('Service d\'exécution indisponible.');
        }
    }

    private function pollResult(string $token, int $maxAttempts = 10): array
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep(1);

            $result = Http::get("{$this->baseUrl}/submissions/{$token}", [
                'base64_encoded' => 'true',
                'fields'         => 'status,stdout,stderr,time,memory,compile_output',
            ]);

            $data   = $result->json();
            $status = $data['status']['id'] ?? 0;

            // Status 1 = In Queue, 2 = Processing
            if (in_array($status, [1, 2])) {
                continue;
            }

            return [
                'status'         => $data['status']['description'] ?? 'Unknown',
                'stdout'         => $data['stdout'] ? base64_decode($data['stdout']) : null,
                'stderr'         => $data['stderr'] ? base64_decode($data['stderr']) : null,
                'compile_output' => $data['compile_output'] ? base64_decode($data['compile_output']) : null,
                'time_ms'        => $data['time'] ? (float)$data['time'] * 1000 : null,
                'memory_kb'      => $data['memory'] ?? null,
                'success'        => $status === 3, // 3 = Accepted
            ];
        }

        return $this->errorResult('Timeout — exécution trop longue.');
    }

    private function errorResult(string $message): array
    {
        return [
            'status'         => 'Error',
            'stdout'         => null,
            'stderr'         => $message,
            'compile_output' => null,
            'time_ms'        => null,
            'memory_kb'      => null,
            'success'        => false,
        ];
    }

    public function getSupportedLanguages(): array
    {
        return array_keys($this->languageMap);
    }
}

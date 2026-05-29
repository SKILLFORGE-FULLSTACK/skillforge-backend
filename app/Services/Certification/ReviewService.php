<?php

namespace App\Services\Certification;

use App\Models\Certification;
use App\Models\CertificationSubmission;
use App\Services\Interview\GroqService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReviewService
{
    public function __construct(private GroqService $groq) {}

    public function reviewProject(CertificationSubmission $submission): array
    {
        $certification = $submission->certification;

        $repoAnalysis = $this->analyzeGithubRepo($submission->github_repo_url);

        $criteria    = json_encode($certification->evaluation_criteria, JSON_UNESCAPED_UNICODE);
        $repoData    = json_encode($repoAnalysis, JSON_UNESCAPED_UNICODE);
        $liveUrl     = $submission->live_url ?? 'non fournie';
        $notes       = $submission->notes ?? 'aucune';
        $certTitle   = $certification->title;
        $certLevel   = $certification->level;
        $certBrief   = $certification->project_brief;
        $passScore   = $certification->passing_score;

        $prompt = <<<PROMPT
            Tu es un reviewer technique senior. Évalue ce projet de certification.

            Certification : {$certTitle} (niveau {$certLevel})
            Cahier des charges : {$certBrief}

            Critères d'évaluation : {$criteria}

            Données du repo analysé : {$repoData}
            URL live : {$liveUrl}
            Notes du candidat : {$notes}

            Réponds UNIQUEMENT en JSON valide :
            {
            "score_total": 78,
            "score_breakdown": {
                "code_quality": 80,
                "architecture": 75,
                "tests": 60,
                "documentation": 85,
                "security": 70
            },
            "verdict": "passed|failed",
            "strengths": ["point fort 1", "point fort 2"],
            "improvements": ["amélioration 1", "amélioration 2"],
            "feedback": "Feedback détaillé et constructif en français (5-8 phrases)",
            "security_issues": ["problème securite si trouve"],
            "missing_requirements": ["exigence manquante si applicable"]
            }

            Score de passage minimum : {$passScore}/100
            PROMPT;

        $response = $this->groq->chat([
            ['role' => 'user', 'content' => $prompt],
        ], 1500);

        try {
            $cleaned = preg_replace('/```json|```/', '', $response);
            return json_decode(trim($cleaned), true) ?? $this->fallbackReview();
        } catch (\Exception $e) {
            Log::error('Review parsing error', ['error' => $e->getMessage()]);
            return $this->fallbackReview();
        }
    }

    private function analyzeGithubRepo(string $repoUrl): array
    {
        // Extraire owner/repo depuis l'URL
        preg_match('/github\.com\/([^\/]+)\/([^\/\?#]+)/', $repoUrl, $matches);

        if (count($matches) < 3) {
            return ['error' => 'URL GitHub invalide'];
        }

        $owner = $matches[1];
        $repo  = rtrim($matches[2], '.git');

        try {
            $headers = ['Accept' => 'application/vnd.github.v3+json'];

            if ($token = config('services.github.token')) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            // Infos du repo
            $repoInfo = Http::withHeaders($headers)
                ->get("https://api.github.com/repos/{$owner}/{$repo}")
                ->json();

            // Langages utilisés
            $languages = Http::withHeaders($headers)
                ->get("https://api.github.com/repos/{$owner}/{$repo}/languages")
                ->json();

            // Fichiers racine
            $contents = Http::withHeaders($headers)
                ->get("https://api.github.com/repos/{$owner}/{$repo}/contents")
                ->json();

            $files = is_array($contents)
                ? collect($contents)->pluck('name')->toArray()
                : [];

            return [
                'name'          => $repoInfo['name'] ?? $repo,
                'description'   => $repoInfo['description'] ?? null,
                'stars'         => $repoInfo['stargazers_count'] ?? 0,
                'languages'     => array_keys($languages ?? []),
                'has_readme'    => in_array('README.md', $files) || in_array('readme.md', $files),
                'has_tests'     => in_array('tests', $files) || in_array('test', $files),
                'has_docker'    => in_array('Dockerfile', $files) || in_array('docker-compose.yml', $files),
                'has_env_example' => in_array('.env.example', $files),
                'files_root'    => $files,
                'last_commit'   => $repoInfo['pushed_at'] ?? null,
                'commits_count' => $repoInfo['size'] ?? 0,
            ];

        } catch (\Exception $e) {
            Log::warning('GitHub API error', ['error' => $e->getMessage()]);
            return ['error' => 'Impossible d\'analyser le repo', 'url' => $repoUrl];
        }
    }

    private function fallbackReview(): array
    {
        return [
            'score_total'          => 0,
            'score_breakdown'      => [],
            'verdict'              => 'failed',
            'strengths'            => [],
            'improvements'         => ['Review automatique indisponible'],
            'feedback'             => 'La review automatique est temporairement indisponible. Un reviewer humain va analyser votre projet.',
            'security_issues'      => [],
            'missing_requirements' => [],
        ];
    }
}

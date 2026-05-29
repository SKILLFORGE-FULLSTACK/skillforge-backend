<?php

namespace Database\Seeders;

use App\Models\Certification;
use Illuminate\Database\Seeder;

class CertificationSeeder extends Seeder
{
    public function run(): void
    {
        $certifications = [
            [
                'slug'     => 'laravel-api-intermediate',
                'title'    => 'Laravel 12 API — Niveau Intermédiaire',
                'category' => 'backend',
                'level'    => 'mid',
                'description' => 'Construisez une API REST complète avec Laravel 12 : authentification, CRUD, pagination, tests et documentation.',
                'skills_covered' => ['Laravel', 'PHP', 'PostgreSQL', 'REST API'],
                'project_brief' => "Construisez une API de gestion de tâches (todo app) avec :\n- Auth avec Laravel Sanctum\n- CRUD complet sur les tâches\n- Pagination et filtres\n- Tests Feature (min 80% coverage)\n- Documentation Swagger/Postman\n- Déploiement sur Railway ou Render",
                'evaluation_criteria' => [
                    'code_quality'  => ['weight' => 25, 'description' => 'Clean code, PSR-12, conventions Laravel'],
                    'architecture'  => ['weight' => 25, 'description' => 'Services, Resources, Requests, Repository pattern'],
                    'tests'         => ['weight' => 20, 'description' => 'Coverage, cas limites, factories'],
                    'documentation' => ['weight' => 15, 'description' => 'README, Swagger/Postman collection'],
                    'security'      => ['weight' => 15, 'description' => 'Validation, autorisation, injection SQL'],
                ],
                'passing_score'    => 70,
                'duration_days'    => 7,
                'validity_months'  => 24,
                'price_credits'    => 1,
                'attempts_allowed' => 2,
                'badge_color'      => '#7C3AED',
                'is_active'        => true,
            ],
            [
                'slug'     => 'react-nextjs-frontend',
                'title'    => 'React & Next.js 15 — Développeur Frontend',
                'category' => 'frontend',
                'level'    => 'mid',
                'description' => 'Créez une application Next.js 15 performante avec SSR, composants réutilisables et intégration API.',
                'skills_covered' => ['React', 'Next.js', 'TypeScript', 'TailwindCSS'],
                'project_brief' => "Créez un dashboard analytics avec :\n- Next.js 15 App Router\n- Authentification (NextAuth)\n- Graphiques interactifs (Recharts ou Chart.js)\n- Fetching API avec React Query\n- Responsive design avec Tailwind\n- TypeScript strict\n- Déploiement Vercel",
                'evaluation_criteria' => [
                    'code_quality'  => ['weight' => 20, 'description' => 'TypeScript, composants réutilisables'],
                    'architecture'  => ['weight' => 25, 'description' => 'App Router, Server Components, structure'],
                    'performance'   => ['weight' => 20, 'description' => 'Lighthouse score, lazy loading, SSR'],
                    'ui_ux'         => ['weight' => 20, 'description' => 'Design, responsive, accessibilité'],
                    'tests'         => ['weight' => 15, 'description' => 'Jest, React Testing Library'],
                ],
                'passing_score'    => 70,
                'duration_days'    => 7,
                'validity_months'  => 24,
                'price_credits'    => 1,
                'attempts_allowed' => 2,
                'badge_color'      => '#0EA5E9',
                'is_active'        => true,
            ],
            [
                'slug'     => 'fullstack-laravel-next',
                'title'    => 'Fullstack Laravel + Next.js — Niveau Senior',
                'category' => 'fullstack',
                'level'    => 'senior',
                'description' => 'Projet fullstack complet avec architecture microservices légère, Docker, CI/CD et sécurité avancée.',
                'skills_covered' => ['Laravel', 'Next.js', 'Docker', 'PostgreSQL', 'Redis'],
                'project_brief' => "Construisez un SaaS minimal de gestion de projets avec :\n- Backend Laravel 12 (API)\n- Frontend Next.js 15\n- Auth JWT + refresh tokens\n- Rôles et permissions (admin, member)\n- Notifications temps réel (WebSockets ou SSE)\n- Docker Compose complet\n- GitHub Actions CI/CD\n- Tests E2E Playwright",
                'evaluation_criteria' => [
                    'architecture'  => ['weight' => 30, 'description' => 'Séparation backend/frontend, API design'],
                    'code_quality'  => ['weight' => 20, 'description' => 'Clean code, patterns, conventions'],
                    'devops'        => ['weight' => 20, 'description' => 'Docker, CI/CD, environnements'],
                    'security'      => ['weight' => 15, 'description' => 'Auth, CORS, validation, XSS'],
                    'tests'         => ['weight' => 15, 'description' => 'Unit, Feature, E2E'],
                ],
                'passing_score'    => 75,
                'duration_days'    => 14,
                'validity_months'  => 24,
                'price_credits'    => 2,
                'attempts_allowed' => 2,
                'badge_color'      => '#F59E0B',
                'is_active'        => true,
            ],
        ];

        foreach ($certifications as $data) {
            Certification::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $this->command->info('✅ 3 certifications créées.');
    }
}

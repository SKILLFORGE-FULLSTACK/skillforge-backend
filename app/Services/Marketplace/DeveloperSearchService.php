<?php

namespace App\Services\Marketplace;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DeveloperSearchService
{
    public function search(array $filters): LengthAwarePaginator
    {
        $query = User::query()
            ->where('role', 'developer')
            ->where('is_public', true)
            ->with([
                'developerProfile',
                'skills'  => fn($q) => $q->where('is_certified', true)->limit(6),
                'badges'  => fn($q) => $q->where('badge_type', 'certification')
                    ->where('is_public', true)
                    ->latest('issued_at')
                    ->limit(4),
            ]);

        $this->applyFilters($query, $filters);
        $this->applySort($query, $filters['sort'] ?? 'score');

        return $query->paginate($filters['per_page'] ?? 12);
    }

    public function findByIdentifier(string $identifier): User
    {
        return User::where(function ($query) use ($identifier) {
            $query->where('username', $identifier);
            if (preg_match('/^[0-9a-f-]{36}$/i', $identifier)) {
                $query->orWhere('id', $identifier);
            }
        })
            ->where('role', 'developer')
            ->where('is_public', true)
            ->with([
                'developerProfile',
                'skills',
                'badges.certification',
                'interviewSessions' => fn($q) => $q->where('status', 'completed')
                    ->latest()
                    ->limit(5),
            ])
            ->firstOrFail();
    }

    public function isSavedByRecruiter(string $developerId, string $recruiterId): bool
    {
        return \App\Models\RecruiterSavedProfile::where('recruiter_id', $recruiterId)
            ->where('developer_id', $developerId)
            ->exists();
    }

    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['is_available'])) {
            $query->where('is_available', true);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('username', 'ilike', "%{$search}%")
                    ->orWhereHas(
                        'developerProfile',
                        fn($q) =>
                        $q->where('headline', 'ilike', "%{$search}%")
                            ->orWhere('bio', 'ilike', "%{$search}%")
                    );
            });
        }

        if (! empty($filters['level'])) {
            $query->whereHas(
                'developerProfile',
                fn($q) =>
                $q->where('current_level', $filters['level'])
            );
        }

        if (! empty($filters['work_mode'])) {
            $query->whereHas(
                'developerProfile',
                fn($q) =>
                $q->where('work_mode', $filters['work_mode'])
                    ->orWhere('work_mode', 'any')
            );
        }

        if (! empty($filters['location_country'])) {
            $query->whereHas(
                'developerProfile',
                fn($q) =>
                $q->where('location_country', 'ilike', "%{$filters['location_country']}%")
            );
        }

        if (! empty($filters['salary_max'])) {
            $query->whereHas(
                'developerProfile',
                fn($q) =>
                $q->where('target_salary_min', '<=', $filters['salary_max'])
                    ->orWhereNull('target_salary_min')
            );
        }

        if (! empty($filters['skills'])) {
            foreach (array_map('trim', explode(',', $filters['skills'])) as $skill) {
                $query->whereHas(
                    'skills',
                    fn($q) =>
                    $q->where('skill_name', 'ilike', "%{$skill}%")
                );
            }
        }

        if (! empty($filters['certifications'])) {
            foreach (array_map('trim', explode(',', $filters['certifications'])) as $cert) {
                $query->whereHas(
                    'badges.certification',
                    fn($q) =>
                    $q->where('slug', $cert)
                );
            }
        }
    }

    private function applySort($query, string $sort): void
    {
        match ($sort) {
            'recent' => $query->latest('created_at'),
            'streak' => $query->orderByDesc('current_streak'),
            default  => $query->orderByDesc(
                DB::raw('(SELECT overall_score FROM developer_profiles WHERE user_id = users.id)')
            ),
        };
    }
}

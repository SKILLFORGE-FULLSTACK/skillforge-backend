<?php

namespace App\Services\Marketplace;

use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class JobPostingService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = JobPosting::query()
            ->with([
                'recruiter:id,name',
                'recruiter.recruiterProfile:user_id,company_name,company_logo_url',
            ])
            ->latest('published_at');

        if (! empty($filters['recruiter_id'])) {
            // Vue "mes offres" d'un recruteur : toutes ses offres, quel que
            // soit leur statut (active/paused/closed).
            $query->where('recruiter_id', $filters['recruiter_id']);
        } else {
            // Marketplace publique : uniquement les offres actives, tous recruteurs.
            $query->where('status', 'active');
        }

        if (! empty($filters['contract_type'])) {
            $query->where('contract_type', $filters['contract_type']);
        }

        if (! empty($filters['work_mode'])) {
            $query->where('work_mode', $filters['work_mode']);
        }

        if (! empty($filters['search'])) {
            $query->where(
                fn ($q) => $q->where('title', 'ilike', "%{$filters['search']}%")
                    ->orWhere('description', 'ilike', "%{$filters['search']}%")
            );
        }

        return $query->paginate($filters['per_page'] ?? 10);
    }

    public function create(User $recruiter, array $data): JobPosting
    {
        return JobPosting::create([
            ...$data,
            'recruiter_id' => $recruiter->id,
            'currency' => $data['currency'] ?? 'USD',
        ]);
    }

    public function findOrFail(string $id): JobPosting
    {
        $job = JobPosting::with([
            'recruiter:id,name',
            'recruiter.recruiterProfile:user_id,company_name,company_logo_url,company_website,industry',
        ])->findOrFail($id);

        $job->increment('views_count');

        return $job;
    }

    public function update(User $recruiter, string $id, array $data): JobPosting
    {
        $job = JobPosting::where('recruiter_id', $recruiter->id)->findOrFail($id);
        $job->update($data);

        return $job;
    }
}

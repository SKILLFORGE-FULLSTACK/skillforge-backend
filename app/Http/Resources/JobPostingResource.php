<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobPostingResource extends JsonResource
{
    use ResolvesLocale;

    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);

        return [
            'id' => $this->id,
            'title' => $this->translated('title', $locale),
            'description' => $this->translated('description', $locale),
            'contract_type' => $this->contract_type,
            'work_mode' => $this->work_mode,
            'min_level' => $this->min_level,
            'required_skills' => $this->required_skills,
            'required_certs' => $this->required_certs,
            'location' => $this->location,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'currency' => $this->currency,
            'status' => $this->status,
            'views_count' => $this->views_count,
            'applications_count' => $this->applications_count,
            'published_at' => $this->published_at?->format('Y-m-d'),
            'expires_at' => $this->expires_at?->format('Y-m-d'),
            'recruiter' => $this->whenLoaded('recruiter', fn () => [
                'id' => $this->recruiter->id,
                'name' => $this->recruiter->name,
                'company_name' => $this->recruiter->recruiterProfile?->company_name,
                'company_logo' => $this->recruiter->recruiterProfile?->company_logo_url,
                'industry' => $this->recruiter->recruiterProfile?->industry,
            ]),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeveloperProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'headline'              => $this->headline,
            'current_level'         => $this->current_level,
            'target_level'          => $this->target_level,
            'years_experience'      => $this->years_experience,
            'overall_score'         => $this->overall_score,
            'interview_score'       => $this->interview_score,
            'cert_score'            => $this->cert_score,
            'certifications_count'  => $this->certifications_count,
            'work_mode'             => $this->work_mode,
            'preferred_contract'    => $this->preferred_contract,
            'location_country'      => $this->location_country,
            'location_city'         => $this->location_city,
            'target_salary_min'     => $this->target_salary_min,
            'target_salary_max'     => $this->target_salary_max,
            // Champs détaillés (profil complet seulement)
            'bio'                   => $this->when(
                $this->resource->relationLoaded('user'),
                $this->bio
            ),
            'linkedin_url'          => $this->linkedin_url,
            'portfolio_url'         => $this->portfolio_url,
            'github_stats'          => $this->github_stats,
        ];
    }
}

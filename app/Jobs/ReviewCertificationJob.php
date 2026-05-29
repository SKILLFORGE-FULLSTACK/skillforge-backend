<?php

namespace App\Jobs;

use App\Services\Certification\CertificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReviewCertificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(public string $submissionId) {}

    public function handle(CertificationService $service): void
    {
        $service->processReview($this->submissionId);
    }
}

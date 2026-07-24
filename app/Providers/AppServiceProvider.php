<?php

namespace App\Providers;

use App\Models\Certification;
use App\Models\InterviewCategory;
use App\Models\InterviewQuestion;
use App\Models\JobPosting;
use App\Observers\TranslatableObserver;
use App\Services\Translation\GoogleTranslateService;
use App\Services\Translation\LibreTranslateService;
use App\Services\Translation\TranslatorService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Moteur de traduction interchangeable via TRANSLATION_DRIVER (voir
        // config/services.php) — "libretranslate" (gratuit, auto-hébergé) par
        // défaut, "google" nécessite une clé Cloud Translation API facturée.
        $this->app->bind(TranslatorService::class, function () {
            return config('services.translation.driver') === 'google'
                ? new GoogleTranslateService
                : new LibreTranslateService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Traduction FR → EN automatique en tâche de fond pour le contenu
        // "Translatable" (voir App\Observers\TranslatableObserver).
        Certification::observe(TranslatableObserver::class);
        JobPosting::observe(TranslatableObserver::class);
        InterviewQuestion::observe(TranslatableObserver::class);
        InterviewCategory::observe(TranslatableObserver::class);
    }
}

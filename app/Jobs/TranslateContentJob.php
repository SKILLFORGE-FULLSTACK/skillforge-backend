<?php

namespace App\Jobs;

use App\Services\Translation\TranslatorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;

class TranslateContentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, string>  $fields  colonne source (fr) => colonne cible (ex: title => title_en)
     */
    public function __construct(
        private string $modelClass,
        private string $modelId,
        private array $fields,
        private string $target = 'en',
        private string $source = 'fr',
    ) {}

    public function handle(TranslatorService $translator): void
    {
        $model = $this->modelClass::find($this->modelId);

        if (! $model) {
            return;
        }

        $updates = [];

        foreach ($this->fields as $sourceField => $targetField) {
            $sourceText = (string) $model->{$sourceField};

            if ($sourceText === '') {
                continue;
            }

            $translated = $translator->translate($sourceText, $this->target, $this->source);

            if ($translated !== null) {
                $updates[$targetField] = $translated;
            }
        }

        if ($updates !== []) {
            // updateQuietly : ne redéclenche pas TranslatableObserver (éviterait
            // une boucle infinie de jobs de traduction).
            $model->updateQuietly($updates);
        }
    }
}

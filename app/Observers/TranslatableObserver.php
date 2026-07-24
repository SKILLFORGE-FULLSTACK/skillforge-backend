<?php

namespace App\Observers;

use App\Contracts\Translatable;
use App\Jobs\TranslateContentJob;
use Illuminate\Database\Eloquent\Model;

/**
 * Traduction automatique en tâche de fond (FR → EN) pour tout modèle qui
 * implémente Translatable. À la création : traduit les champs renseignés.
 * À la modification : ne retraduit que les champs source qui ont changé.
 */
class TranslatableObserver
{
    public function created(Model $model): void
    {
        if (! $model instanceof Translatable) {
            return;
        }

        $this->dispatch($model, $this->filled($model, $model::translatableFields()));
    }

    public function updated(Model $model): void
    {
        if (! $model instanceof Translatable) {
            return;
        }

        $changed = array_filter(
            $model::translatableFields(),
            fn (string $target, string $source) => $model->wasChanged($source),
            ARRAY_FILTER_USE_BOTH,
        );

        $this->dispatch($model, $this->filled($model, $changed));
    }

    /** @return array<string, string> */
    private function filled(Model $model, array $fields): array
    {
        return array_filter($fields, fn (string $source) => filled($model->{$source}), ARRAY_FILTER_USE_KEY);
    }

    private function dispatch(Model $model, array $fields): void
    {
        if ($fields === []) {
            return;
        }

        TranslateContentJob::dispatch($model::class, $model->getKey(), $fields);
    }
}

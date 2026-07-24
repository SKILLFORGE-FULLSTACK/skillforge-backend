<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Les catégories d'entretien (jusqu'ici un tableau figé côté frontend +
     * un ENUM Postgres sur `type`) deviennent une vraie table gérable depuis
     * une interface admin. `interview_categories.key` devient directement la
     * valeur stockée dans `interview_sessions.type` / `interview_questions.type`
     * — plus besoin de migration pour ajouter une catégorie.
     */
    public function up(): void
    {
        Schema::create('interview_categories', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('key', 100)->unique(); // valeur stockée dans interview_sessions.type
            $table->string('label');       // français (langue de rédaction par défaut)
            $table->string('label_en')->nullable();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->enum('default_difficulty', ['easy', 'medium', 'hard', 'expert'])->default('medium');
            $table->string('stack_focus', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // `type` sur interview_sessions/interview_questions n'était pas un vrai
        // ENUM Postgres : Laravel l'émule via une contrainte CHECK sur une
        // colonne varchar. On la retire pour accepter n'importe quelle clé
        // définie dans interview_categories.
        DB::statement('ALTER TABLE interview_sessions DROP CONSTRAINT IF EXISTS interview_sessions_type_check');
        DB::statement('ALTER TABLE interview_questions DROP CONSTRAINT IF EXISTS interview_questions_type_check');

        $now = now();
        DB::table('interview_categories')->insert([
            ['id' => (string) Str::uuid(), 'key' => 'algo', 'label' => 'Algorithmes', 'label_en' => 'Algorithms', 'default_difficulty' => 'medium', 'stack_focus' => null, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::uuid(), 'key' => 'system_design', 'label' => 'System Design', 'label_en' => 'System Design', 'default_difficulty' => 'hard', 'stack_focus' => null, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::uuid(), 'key' => 'frontend', 'label' => 'Frontend', 'label_en' => 'Frontend', 'default_difficulty' => 'medium', 'stack_focus' => 'frontend', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::uuid(), 'key' => 'backend', 'label' => 'Backend', 'label_en' => 'Backend', 'default_difficulty' => 'medium', 'stack_focus' => 'backend', 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::uuid(), 'key' => 'behavioral', 'label' => 'Comportemental', 'label_en' => 'Behavioral', 'default_difficulty' => 'easy', 'stack_focus' => null, 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::uuid(), 'key' => 'code_review', 'label' => 'Revue de code', 'label_en' => 'Code Review', 'default_difficulty' => 'medium', 'stack_focus' => null, 'sort_order' => 6, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::uuid(), 'key' => 'debug', 'label' => 'Débogage', 'label_en' => 'Debugging', 'default_difficulty' => 'medium', 'stack_focus' => null, 'sort_order' => 7, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::uuid(), 'key' => 'live_coding', 'label' => 'Live Coding', 'label_en' => 'Live Coding', 'default_difficulty' => 'hard', 'stack_focus' => null, 'sort_order' => 8, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE interview_sessions ADD CONSTRAINT interview_sessions_type_check CHECK ((type)::text = ANY (ARRAY['algo','system_design','behavioral','code_review','debug','tech_stack','live_coding']::text[]))");
        DB::statement("ALTER TABLE interview_questions ADD CONSTRAINT interview_questions_type_check CHECK ((type)::text = ANY (ARRAY['algo','system_design','behavioral','code_review','debug','tech_stack','live_coding']::text[]))");

        Schema::dropIfExists('interview_categories');
    }
};

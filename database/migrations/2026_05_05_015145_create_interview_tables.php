<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── BANQUE DE QUESTIONS ────────────────────────────────────────
        Schema::create('interview_questions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('category', 100);             // arrays, trees, system_design...
            $table->enum('type', [
                'algo',
                'system_design',
                'behavioral',
                'code_review',
                'debug',
                'tech_stack',
                'live_coding'
            ]);
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'expert']);
            $table->string('title', 500);
            $table->text('description');
            $table->text('constraints')->nullable();
            $table->json('examples')->nullable();        // [{input, output, explanation}]
            $table->json('hints')->nullable();           // hints progressifs
            $table->text('solution')->nullable();        // solution de référence
            $table->json('tags')->nullable();
            $table->json('companies')->nullable();       // ["Google", "Meta"]
            $table->json('stack')->nullable();           // ["React", "Laravel"]
            $table->integer('times_asked')->default(0);
            $table->decimal('avg_score', 4, 2)->nullable();
            $table->boolean('is_community')->default(false);
            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'difficulty']);
            $table->index('category');
        });

        // ─── SESSIONS D'INTERVIEW ───────────────────────────────────────
        Schema::create('interview_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            $table->enum('type', [
                'algo',
                'system_design',
                'behavioral',
                'code_review',
                'debug',
                'tech_stack',
                'live_coding'
            ]);
            $table->enum('mode', ['practice', 'mock', 'company_sim'])->default('mock');
            $table->string('company_target', 100)->nullable(); // "Google", "startup"
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'expert'])->default('medium');
            $table->string('stack_focus', 100)->nullable();    // "React", "Laravel"
            $table->smallInteger('duration_min')->nullable();
            $table->integer('actual_duration_sec')->nullable();

            $table->enum('status', [
                'in_progress',
                'completed',
                'abandoned'
            ])->default('in_progress');

            // Résultats
            $table->decimal('score_total', 5, 2)->nullable();
            $table->json('score_breakdown')->nullable();
            $table->text('ai_feedback')->nullable();
            $table->smallInteger('questions_count')->default(0);
            $table->smallInteger('hints_used')->default(0);
            $table->string('recording_url')->nullable();
            $table->smallInteger('xp_earned')->default(0);

            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'type']);
        });

        // ─── RÉPONSES AUX QUESTIONS ─────────────────────────────────────
        Schema::create('interview_responses', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('session_id')
                ->constrained('interview_sessions')
                ->cascadeOnDelete();
            $table->foreignUuid('question_id')
                ->constrained('interview_questions')
                ->cascadeOnDelete();

            // Code soumis
            $table->text('code_submitted')->nullable();
            $table->string('language', 50)->nullable();
            $table->text('text_answer')->nullable();

            // Résultat exécution (Judge0)
            $table->json('execution_result')->nullable(); // {stdout, stderr, time_ms, memory_kb, status}

            // Score et analyse
            $table->decimal('score', 5, 2)->nullable();
            $table->json('ai_analysis')->nullable();      // analyse détaillée par critère
            $table->smallInteger('hints_used')->default(0);
            $table->integer('time_taken_sec')->nullable();

            $table->timestamps();

            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_responses');
        Schema::dropIfExists('interview_sessions');
        Schema::dropIfExists('interview_questions');
    }
};

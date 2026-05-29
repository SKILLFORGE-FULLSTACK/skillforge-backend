<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── CERTIFICATIONS (catalogue) ─────────────────────────────────
        Schema::create('certifications', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('slug', 100)->unique();
            $table->string('title');
            $table->string('category', 100);            // backend, frontend, devops...
            $table->enum('level', ['junior', 'mid', 'senior']);
            $table->text('description')->nullable();
            $table->json('skills_covered')->nullable();  // ["Laravel", "PostgreSQL"]
            $table->text('project_brief');               // cahier des charges
            $table->json('evaluation_criteria');         // critères et poids
            $table->smallInteger('passing_score')->default(75);
            $table->smallInteger('duration_days')->default(7);
            $table->smallInteger('validity_months')->default(24);
            $table->smallInteger('price_credits')->default(1);
            $table->smallInteger('attempts_allowed')->default(2);
            $table->string('badge_svg_url')->nullable();
            $table->string('badge_color', 7)->default('#7C3AED');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ─── SOUMISSIONS DE CERTIFICATION ───────────────────────────────
        Schema::create('certification_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('certification_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('attempt_number')->default(1);
            $table->enum('status', [
                'in_progress',
                'submitted',
                'reviewing',
                'passed',
                'failed'
            ])->default('in_progress');

            // Soumission
            $table->string('github_repo_url')->nullable();
            $table->string('live_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('deadline_at')->nullable();

            // Résultats
            $table->decimal('score_total', 5, 2)->nullable();
            $table->json('score_breakdown')->nullable();  // {qualite: 85, tests: 70...}
            $table->text('ai_review')->nullable();
            $table->text('human_review')->nullable();
            $table->foreignUuid('human_reviewer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->smallInteger('xp_earned')->default(0);

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['certification_id', 'status']);
        });

        // ─── BADGES UTILISATEUR ─────────────────────────────────────────
        Schema::create('user_badges', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('certification_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignUuid('submission_id')
                ->nullable()
                ->constrained('certification_submissions')
                ->nullOnDelete();

            $table->enum('badge_type', [
                'certification',
                'achievement',
                'streak',
                'community',
                'special'
            ])->default('certification');

            // Token de vérification public unique
            $table->string('verify_token', 64)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->string('badge_image_url')->nullable();

            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_public')->default(true);
            $table->integer('share_count')->default(0);

            $table->timestamps();

            $table->index('verify_token');
            $table->index(['user_id', 'badge_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('certification_submissions');
        Schema::dropIfExists('certifications');
    }
};

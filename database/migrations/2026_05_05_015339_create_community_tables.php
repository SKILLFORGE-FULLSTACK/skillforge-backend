<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── FORUM POSTS ────────────────────────────────────────────────
        Schema::create('forum_posts', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 500);
            $table->text('body');
            $table->string('category', 100)->nullable();
            $table->json('tags')->nullable();
            $table->integer('votes')->default(0);
            $table->integer('views')->default(0);
            $table->integer('answers_count')->default(0);
            $table->boolean('is_answered')->default(false);
            $table->uuid('accepted_answer_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'created_at']);
            $table->index('user_id');
        });

        // ─── COMMENTAIRES FORUM ─────────────────────────────────────────
        // Sans le parent_id FK d'abord
        Schema::create('forum_comments', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('post_id')
                ->constrained('forum_posts')
                ->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('parent_id')->nullable(); // pas de FK ici encore
            $table->text('body');
            $table->integer('votes')->default(0);
            $table->boolean('is_accepted')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['post_id', 'created_at']);
        });

        // Ajouter la FK auto-référentielle APRÈS la création de la table
        Schema::table('forum_comments', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('forum_comments')
                ->nullOnDelete();
        });

        // Ajouter la FK accepted_answer_id sur forum_posts APRÈS forum_comments
        Schema::table('forum_posts', function (Blueprint $table) {
            $table->foreign('accepted_answer_id')
                ->references('id')
                ->on('forum_comments')
                ->nullOnDelete();
        });

        // ─── VOTES (posts et commentaires) ──────────────────────────────
        Schema::create('votes', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('votable_type', 50);          // forum_posts, forum_comments
            $table->uuid('votable_id');
            $table->tinyInteger('value')->default(1);    // 1 = upvote, -1 = downvote
            $table->timestamps();

            $table->unique(['user_id', 'votable_type', 'votable_id']);
            $table->index(['votable_type', 'votable_id']);
        });

        // ─── PEER REVIEWS ───────────────────────────────────────────────
        Schema::create('community_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('reviewee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('submission_id')
                ->nullable()
                ->constrained('certification_submissions')
                ->nullOnDelete();
            $table->enum('type', ['peer_review', 'mentor_review'])->default('peer_review');
            $table->decimal('score', 5, 2)->nullable();
            $table->text('feedback');
            $table->smallInteger('xp_earned')->default(0);
            $table->timestamps();

            $table->index('reviewer_id');
            $table->index('reviewee_id');
        });

        // ─── NOTIFICATIONS ───────────────────────────────────────────────
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 100);                 // "cert_passed", "recruiter_contact"
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();             // payload contextuel
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'created_at']);
        });

        // ─── CHALLENGES HEBDOMADAIRES ────────────────────────────────────
        Schema::create('weekly_challenges', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('question_id')
                ->constrained('interview_questions')
                ->cascadeOnDelete();
            $table->date('week_start')->unique();
            $table->integer('participants_count')->default(0);
            $table->timestamps();
        });

        Schema::create('challenge_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('challenge_id')
                ->constrained('weekly_challenges')
                ->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->text('code_submitted');
            $table->string('language', 50);
            $table->json('execution_result')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->integer('rank')->nullable();
            $table->timestamps();

            $table->unique(['challenge_id', 'user_id']);
            $table->index('challenge_id');
        });
    }

    public function down(): void
    {
        // Supprimer les FKs croisées d'abord
        Schema::table('forum_posts', function (Blueprint $table) {
            $table->dropForeign(['accepted_answer_id']);
        });
        Schema::table('forum_comments', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
        });

        Schema::dropIfExists('challenge_submissions');
        Schema::dropIfExists('weekly_challenges');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('community_reviews');
        Schema::dropIfExists('votes');
        Schema::dropIfExists('forum_comments');
        Schema::dropIfExists('forum_posts');
    }
};

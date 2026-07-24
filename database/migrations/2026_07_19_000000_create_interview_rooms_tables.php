<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── SALLES D'ENTRETIEN (candidat + IA, extensible multi-participants) ──
        Schema::create('interview_rooms', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('job_posting_id')->nullable()->constrained('job_postings')->nullOnDelete();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();

            $table->enum('mode', ['practice', 'job_interview'])->default('practice');
            $table->boolean('ai_participates')->default(true);
            $table->string('livekit_room_name')->nullable(); // réservé à la V2 multi-participants

            $table->enum('status', ['in_progress', 'completed', 'abandoned'])->default('in_progress');
            $table->string('language', 10)->default('fr');

            $table->decimal('score_total', 5, 2)->nullable();
            $table->json('score_breakdown')->nullable();
            $table->text('ai_feedback')->nullable();
            $table->smallInteger('xp_earned')->default(0);
            $table->string('recording_url')->nullable();

            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['created_by', 'status']);
        });

        // ─── PARTICIPANTS D'UNE SALLE (candidat, recruteur(s), IA) ──────────────
        Schema::create('interview_room_participants', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('room_id')->constrained('interview_rooms')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->cascadeOnDelete(); // null = IA
            $table->enum('role', ['candidate', 'recruiter', 'ai', 'observer']);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->index('room_id');
        });

        // ─── TOURS DE PAROLE (transcript + audio, dans l'ordre) ─────────────────
        Schema::create('interview_turns', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('room_id')->constrained('interview_rooms')->cascadeOnDelete();
            $table->foreignUuid('participant_id')->constrained('interview_room_participants')->cascadeOnDelete();

            $table->smallInteger('order');
            $table->text('transcript')->nullable();
            $table->string('audio_url')->nullable();
            $table->integer('duration_sec')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_turns');
        Schema::dropIfExists('interview_room_participants');
        Schema::dropIfExists('interview_rooms');
    }
};

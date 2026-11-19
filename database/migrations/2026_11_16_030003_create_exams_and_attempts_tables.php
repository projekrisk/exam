<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('question_bank_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->enum('execution_mode', ['mandiri', 'terpusat'])->default('mandiri');
            $table->json('target_classes')->nullable();
            $table->string('session_name', 64)->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->unsignedSmallInteger('duration_minutes')->default(90);
            $table->string('token', 12)->nullable();
            $table->boolean('require_token')->default(true);
            $table->boolean('shuffle_questions')->default(true);
            $table->boolean('shuffle_options')->default(true);
            $table->enum('violation_mode', ['strict', 'standard'])->default('strict');
            $table->unsignedTinyInteger('max_violations')->default(3);
            $table->boolean('show_score')->default(false);
            $table->enum('status', ['draft', 'scheduled', 'active', 'finished'])->default('scheduled');
            $table->timestamps();

            $table->index(['status', 'start_at', 'end_at']);
        });

        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('device_id', 128)->nullable()->index();
            $table->json('question_order')->nullable();
            $table->json('option_orders')->nullable();
            $table->enum('status', ['in_progress', 'locked', 'submitted', 'auto_submitted'])->default('in_progress');
            $table->unsignedTinyInteger('violation_count')->default(0);
            $table->dateTime('started_at');
            $table->dateTime('must_end_at');
            $table->dateTime('last_sync_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->decimal('objective_score', 6, 2)->default(0);
            $table->decimal('essay_score', 6, 2)->default(0);
            $table->decimal('final_score', 6, 2)->default(0);
            $table->enum('grading_status', ['auto_graded', 'pending_essay', 'final'])->default('auto_graded');
            $table->string('receipt_code', 32)->nullable()->unique();
            $table->timestamps();

            $table->unique(['exam_id', 'student_id']);
            $table->index(['exam_id', 'status']);
        });

        Schema::create('exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->json('answer_data')->nullable();
            $table->boolean('is_doubtful')->default(false);
            $table->unsignedBigInteger('client_updated_at')->default(0);
            $table->string('idempotency_key', 64)->nullable();
            $table->decimal('score', 6, 2)->nullable();
            $table->boolean('is_graded')->default(false);
            $table->text('teacher_note')->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
        });

        Schema::create('exam_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->string('type', 64);
            $table->string('description')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_violations');
        Schema::dropIfExists('exam_answers');
        Schema::dropIfExists('exam_attempts');
        Schema::dropIfExists('exams');
    }
};

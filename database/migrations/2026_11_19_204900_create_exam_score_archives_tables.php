<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->boolean('scores_finalized')->default(false)->after('scores_released_at');
            $table->timestamp('scores_finalized_at')->nullable()->after('scores_finalized');
            $table->foreignId('scores_finalized_by')->nullable()->after('scores_finalized_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->decimal('manual_score', 8, 2)->nullable()->after('final_score');
            $table->string('teacher_note')->nullable()->after('manual_score');
        });

        Schema::create('exam_score_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->nullable()->constrained('exams')->nullOnDelete();
            $table->foreignId('question_bank_id')->nullable()->constrained('question_banks')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('subject_name');
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('teacher_name');
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('finalized_by_name')->nullable();
            $table->string('exam_title');
            $table->string('session_name')->nullable();
            $table->string('execution_mode')->default('mandiri');
            $table->timestamp('exam_date')->nullable();
            $table->unsignedInteger('total_participants')->default(0);
            $table->timestamp('finalized_at');
            $table->timestamps();
        });

        Schema::create('exam_score_archive_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_score_archive_id')->constrained('exam_score_archives')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('student_nis');
            $table->string('student_nisn')->nullable();
            $table->string('student_name');
            $table->string('student_kelas');
            $table->string('student_ruang')->nullable();
            $table->decimal('objective_score', 8, 2)->default(0);
            $table->decimal('essay_score', 8, 2)->default(0);
            $table->decimal('manual_score', 8, 2)->nullable();
            $table->decimal('final_score', 8, 2)->default(0);
            $table->string('grading_status')->default('final');
            $table->string('teacher_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_score_archive_items');
        Schema::dropIfExists('exam_score_archives');

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn(['manual_score', 'teacher_note']);
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('scores_finalized_by');
            $table->dropColumn(['scores_finalized', 'scores_finalized_at']);
        });
    }
};

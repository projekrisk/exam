<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_score_archives', function (Blueprint $table) {
            $table->unsignedInteger('total_questions')->default(0)->after('total_participants');
            $table->unsignedInteger('objective_count')->default(0)->after('total_questions');
            $table->unsignedInteger('essay_count')->default(0)->after('objective_count');
            $table->unsignedInteger('max_violations')->default(3)->after('essay_count');
        });

        Schema::table('exam_score_archive_items', function (Blueprint $table) {
            $table->unsignedInteger('answered_count')->default(0)->after('student_ruang');
            $table->unsignedInteger('objective_correct')->default(0)->after('answered_count');
            $table->unsignedInteger('objective_wrong')->default(0)->after('objective_correct');
            $table->unsignedInteger('violation_count')->default(0)->after('final_score');
            $table->string('attempt_status')->default('submitted')->after('violation_count');
        });
    }

    public function down(): void
    {
        Schema::table('exam_score_archive_items', function (Blueprint $table) {
            $table->dropColumn(['answered_count', 'objective_correct', 'objective_wrong', 'violation_count', 'attempt_status']);
        });

        Schema::table('exam_score_archives', function (Blueprint $table) {
            $table->dropColumn(['total_questions', 'objective_count', 'essay_count', 'max_violations']);
        });
    }
};

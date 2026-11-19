<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_score_archives', function (Blueprint $table) {
            $table->boolean('visible_to_teacher')->default(true)->after('max_violations');
            $table->boolean('visible_to_admin')->default(true)->after('visible_to_teacher');
        });
    }

    public function down(): void
    {
        Schema::table('exam_score_archives', function (Blueprint $table) {
            $table->dropColumn(['visible_to_teacher', 'visible_to_admin']);
        });
    }
};

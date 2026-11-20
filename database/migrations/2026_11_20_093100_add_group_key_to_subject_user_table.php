<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('subject_user', 'group_key')) {
            Schema::table('subject_user', function (Blueprint $table) {
                $table->unsignedSmallInteger('group_key')->default(1)->after('kelas');
            });
        }

        if (! Schema::hasIndex('subject_user', 'subject_user_subject_id_idx')) {
            Schema::table('subject_user', function (Blueprint $table) {
                $table->index('subject_id', 'subject_user_subject_id_idx');
            });
        }

        if (Schema::hasIndex('subject_user', 'subject_user_subject_id_user_id_kelas_unique')) {
            Schema::table('subject_user', function (Blueprint $table) {
                $table->dropUnique('subject_user_subject_id_user_id_kelas_unique');
            });
        }

        if (! Schema::hasIndex('subject_user', 'subject_user_unique_group')) {
            Schema::table('subject_user', function (Blueprint $table) {
                $table->unique(['subject_id', 'user_id', 'kelas', 'group_key'], 'subject_user_unique_group');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('subject_user', 'subject_user_unique_group')) {
            Schema::table('subject_user', function (Blueprint $table) {
                $table->dropUnique('subject_user_unique_group');
            });
        }

        if (! Schema::hasIndex('subject_user', 'subject_user_subject_id_user_id_kelas_unique')) {
            Schema::table('subject_user', function (Blueprint $table) {
                $table->unique(['subject_id', 'user_id', 'kelas']);
            });
        }

        if (Schema::hasIndex('subject_user', 'subject_user_subject_id_idx')) {
            Schema::table('subject_user', function (Blueprint $table) {
                $table->dropIndex('subject_user_subject_id_idx');
            });
        }

        if (Schema::hasColumn('subject_user', 'group_key')) {
            Schema::table('subject_user', function (Blueprint $table) {
                $table->dropColumn('group_key');
            });
        }
    }
};

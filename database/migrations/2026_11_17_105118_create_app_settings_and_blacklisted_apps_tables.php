<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('blacklisted_apps', function (Blueprint $table) {
            $table->id();
            $table->string('app_name', 120);
            $table->string('package_name', 191)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('app_settings')->insert([
            ['key' => 'school_name', 'value' => 'SMAN 1 Malingping', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'school_logo_path', 'value' => null, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'principal_name', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'principal_nip', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'activity_name', 'value' => 'Penilaian Sumatif Akhir Semester', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'academic_year', 'value' => '2026/2027', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('blacklisted_apps');
        Schema::dropIfExists('app_settings');
    }
};

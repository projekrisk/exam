<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_has_full_access_to_settings_and_master_data(): void
    {
        $superAdmin = User::factory()->create(['username' => 'superadmin1', 'role' => 'super_admin']);

        $this->actingAs($superAdmin)->get('/pengaturan')->assertOk();
        $this->actingAs($superAdmin)->get('/pengguna')->assertOk();
        $this->actingAs($superAdmin)->get('/siswa')->assertOk();
        $this->actingAs($superAdmin)->get('/guru')->assertOk();
        $this->actingAs($superAdmin)->get('/guru/cetak')->assertOk();
        $this->actingAs($superAdmin)->get('/mata-pelajaran')->assertOk();
        $this->actingAs($superAdmin)->get('/kartu-ujian')->assertOk();
    }

    public function test_panitia_cannot_access_settings_and_is_read_only_on_master_data(): void
    {
        $panitia = User::factory()->create(['username' => 'panitia1', 'role' => 'panitia']);

        $this->actingAs($panitia)->get('/pengaturan')->assertForbidden();
        $this->actingAs($panitia)->get('/pengguna')->assertForbidden();
        $this->actingAs($panitia)->post('/pengaturan/kegiatan', [
            'activity_name' => 'UAS',
            'academic_year' => '2026/2027',
        ])->assertForbidden();
        $this->actingAs($panitia)->post('/pengaturan/reset-siswa', [
            'confirm_text' => 'RESET SISWA',
        ])->assertForbidden();

        $this->actingAs($panitia)->get('/siswa')->assertOk();
        $this->actingAs($panitia)->get('/guru')->assertOk();
        $this->actingAs($panitia)->get('/guru/cetak')->assertOk();
        $this->actingAs($panitia)->get('/mata-pelajaran')->assertOk();
        $this->actingAs($panitia)->get('/kartu-ujian')->assertOk();

        $this->actingAs($panitia)->post('/siswa', [])->assertForbidden();
        $this->actingAs($panitia)->post('/siswa/impor', [])->assertForbidden();
        $this->actingAs($panitia)->get('/siswa/template')->assertForbidden();
        $this->actingAs($panitia)->post('/guru', [])->assertForbidden();
        $this->actingAs($panitia)->post('/mata-pelajaran', [])->assertForbidden();
    }

    public function test_guru_cannot_access_settings_master_data_or_exam_cards(): void
    {
        $guru = User::factory()->create(['username' => 'guru1', 'role' => 'guru']);

        $this->actingAs($guru)->get('/pengaturan')->assertForbidden();
        $this->actingAs($guru)->get('/pengguna')->assertForbidden();
        $this->actingAs($guru)->get('/siswa')->assertForbidden();
        $this->actingAs($guru)->get('/guru')->assertForbidden();
        $this->actingAs($guru)->get('/guru/cetak')->assertForbidden();
        $this->actingAs($guru)->get('/mata-pelajaran')->assertForbidden();
        $this->actingAs($guru)->get('/kartu-ujian')->assertForbidden();

        $this->actingAs($guru)->get('/bank-soal')->assertOk();
        $this->actingAs($guru)->get('/ujian')->assertOk();
    }

    public function test_super_admin_can_update_user_name_and_password(): void
    {
        $superAdmin = User::factory()->create(['username' => 'superadmin2', 'role' => 'super_admin']);
        $panitia = User::factory()->create(['username' => 'panitia2', 'name' => 'Panitia Lama', 'role' => 'panitia']);

        $this->actingAs($superAdmin)->put("/pengguna/{$panitia->id}", [
            'username' => 'panitia2',
            'name' => 'Panitia Baru',
            'role' => 'panitia',
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertSame('Panitia Baru', $panitia->fresh()->name);

        $this->actingAs($superAdmin)->post("/pengguna/{$panitia->id}/password")->assertRedirect();

        $this->assertTrue(password_verify('password123', $panitia->fresh()->password));
    }

    public function test_super_admin_can_reset_non_super_admin_users(): void
    {
        $superAdmin = User::factory()->create(['username' => 'superadmin_keep', 'role' => 'super_admin']);
        $panitia = User::factory()->create(['username' => 'panitia_del', 'role' => 'panitia']);
        $guru = User::factory()->create(['username' => 'guru_del', 'role' => 'guru']);

        $this->actingAs($superAdmin)->post('/pengaturan/reset-pengguna', [
            'confirm_text' => 'RESET PENGGUNA',
        ])->assertRedirect();

        $this->assertNotNull(User::find($superAdmin->id));
        $this->assertNull(User::find($panitia->id));
        $this->assertNull(User::find($guru->id));
    }

    public function test_guru_can_create_separate_question_banks_per_target_class_group_for_same_subject(): void
    {
        $superAdmin = User::factory()->create(['username' => 'superadmin_map', 'role' => 'super_admin']);
        $guru = User::factory()->create(['username' => 'guru_sosio', 'role' => 'guru']);
        $subject = \App\Models\Subject::create(['code' => 'SOS', 'name' => 'Sosiologi']);

        $this->actingAs($superAdmin)->put("/guru/{$guru->id}", [
            'nip' => $guru->username,
            'name' => $guru->name,
            'role' => 'guru',
            'bindings' => [
                ['subject_id' => $subject->id, 'classes' => ['X-E1', 'X-E2', 'X-E3']],
                ['subject_id' => $subject->id, 'classes' => ['XII-F3', 'XII-F4', 'XII-F5']],
            ],
        ])->assertRedirect();

        $groups = $guru->fresh()->subjectBindingGroups();
        $this->assertCount(2, $groups);

        $this->actingAs($guru)->post('/bank-soal', [
            'subject_id' => $subject->id,
            'binding_key' => $groups[0]['key'],
            'title' => 'Sosiologi Kelas X',
        ])->assertRedirect();

        $this->actingAs($guru)->post('/bank-soal', [
            'subject_id' => $subject->id,
            'binding_key' => $groups[1]['key'],
            'title' => 'Sosiologi Kelas XII',
        ])->assertRedirect();

        $bankX = \App\Models\QuestionBank::where('title', 'Sosiologi Kelas X')->firstOrFail();
        $bankXII = \App\Models\QuestionBank::where('title', 'Sosiologi Kelas XII')->firstOrFail();

        $this->assertSame(['X-E1', 'X-E2', 'X-E3'], $bankX->target_classes);
        $this->assertSame(['XII-F3', 'XII-F4', 'XII-F5'], $bankXII->target_classes);

        // Guru profile shows mapped subjects & classes; Panitia profile shows Panitia role
        $this->actingAs($guru)->get('/profil')->assertOk()->assertSee('Sosiologi')->assertSee('X-E1')->assertSee('XII-F5');
        $panitia = User::factory()->create(['username' => 'panitia_prof', 'role' => 'panitia']);
        $this->actingAs($panitia)->get('/profil')->assertOk()->assertSee('Wewenang Panitia Ujian');
        $this->actingAs($guru)->get('/')->assertOk()->assertSee('Siswa Diampu');
    }
}

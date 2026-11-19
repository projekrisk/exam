<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamScoreArchive;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoreFinalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_manual_input_finalize_lock_and_persist_after_exam_deletion(): void
    {
        $panitia = User::factory()->create(['username' => 'panitia_test', 'role' => 'panitia']);
        $guru = User::factory()->create(['username' => 'guru_test', 'role' => 'guru']);
        $otherGuru = User::factory()->create(['username' => 'guru_other', 'role' => 'guru']);

        $subject = Subject::create(['code' => 'MTK-T', 'name' => 'Matematika']);
        $student = Student::create([
            'nis' => '99001',
            'nisn' => '00999001',
            'name' => 'Siswa Uji',
            'kelas' => 'X-1',
            'ruang' => 'R1',
            'is_active' => true,
        ]);

        $bank = QuestionBank::create([
            'subject_id' => $subject->id,
            'teacher_id' => $guru->id,
            'title' => 'Bank Uji',
            'target_classes' => ['X-1'],
            'is_ready' => true,
        ]);

        Question::create([
            'question_bank_id' => $bank->id,
            'type' => 'pg',
            'question_text' => '1 + 1 = ?',
            'options' => [
                ['key' => 'A', 'text' => '2', 'is_correct' => true],
                ['key' => 'B', 'text' => '3', 'is_correct' => false],
            ],
            'weight' => 1,
            'order_no' => 1,
        ]);

        $exam = Exam::create([
            'title' => 'UAS Matematika',
            'question_bank_id' => $bank->id,
            'subject_id' => $subject->id,
            'created_by' => $panitia->id,
            'execution_mode' => 'terpusat',
            'target_classes' => ['X-1'],
            'start_at' => now()->subHours(2),
            'end_at' => now()->subHour(),
            'duration_minutes' => 60,
            'status' => 'finished',
            'scores_released' => false,
        ]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => 'submitted',
            'started_at' => now()->subHours(2),
            'must_end_at' => now()->subHour(),
            'submitted_at' => now()->subHour(),
            'objective_score' => 80,
            'essay_score' => 0,
            'final_score' => 80,
            'grading_status' => 'final',
        ]);

        // 1. Before release, guru cannot open grade page
        $this->actingAs($guru)->get("/ujian/{$exam->id}/koreksi")->assertRedirect('/ujian');

        // 2. Panitia releases scores
        $this->actingAs($panitia)->post("/ujian/{$exam->id}/rilis-nilai")->assertRedirect();
        $this->assertTrue($exam->fresh()->scores_released);

        // 3. Guru inputs manual score
        $this->actingAs($guru)->post("/sesi-ujian/{$attempt->id}/koreksi", [
            'manual_score' => 92.5,
            'attempt_note' => 'Nilai remedial',
        ])->assertRedirect();

        $this->assertSame(92.5, (float) $attempt->fresh()->final_score);
        $this->assertSame(92.5, (float) $attempt->fresh()->manual_score);

        // 4. Guru finalizes & locks scores
        $this->actingAs($guru)->post("/ujian/{$exam->id}/finalisasi-nilai")->assertRedirect();
        $this->assertTrue($exam->fresh()->scores_finalized);

        $archive = ExamScoreArchive::first();
        $this->assertNotNull($archive);
        $this->assertSame($guru->id, $archive->teacher_id);
        $this->assertSame(1, $archive->items()->count());
        $this->assertSame(92.5, (float) $archive->items()->first()->final_score);

        // 5. Editing after finalization is blocked
        $this->actingAs($guru)->post("/sesi-ujian/{$attempt->id}/koreksi", [
            'manual_score' => 50,
        ])->assertRedirect();
        $this->assertSame(92.5, (float) $attempt->fresh()->final_score);

        // 6. Panitia deletes exam schedule -> archive & items persist!
        $this->actingAs($panitia)->delete("/ujian/{$exam->id}")->assertRedirect();
        $this->assertNull(Exam::find($exam->id));

        $archive->refresh();
        $this->assertNull($archive->exam_id);
        $this->assertSame(1, $archive->items()->count());

        // 7. Guru (pembuat soal) & Panitia can access Daftar Nilai; unrelated Guru gets 403
        $this->actingAs($guru)->get('/daftar-nilai')->assertOk()->assertSee('UAS Matematika');
        $this->actingAs($guru)->get("/daftar-nilai/{$archive->id}")->assertOk()->assertSee('Siswa Uji')->assertSee('92.5');
        $this->actingAs($panitia)->get('/daftar-nilai')->assertOk()->assertSee('UAS Matematika');
        $this->actingAs($panitia)->get("/daftar-nilai/{$archive->id}")->assertOk()->assertSee('Siswa Uji');
        $this->actingAs($otherGuru)->get("/daftar-nilai/{$archive->id}")->assertForbidden();

        // 8. Guru deletes from their Daftar Nilai -> hidden for Guru, still visible for Panitia
        $this->actingAs($guru)->delete("/daftar-nilai/{$archive->id}")->assertRedirect('/daftar-nilai');
        $this->actingAs($guru)->get('/daftar-nilai')->assertOk()->assertSee('Belum ada nilai yang difinalisasi.');
        $this->actingAs($guru)->get("/daftar-nilai/{$archive->id}")->assertForbidden();
        $this->actingAs($panitia)->get('/daftar-nilai')->assertOk()->assertSee('UAS Matematika');
        $this->actingAs($panitia)->get("/daftar-nilai/{$archive->id}")->assertOk()->assertSee('Siswa Uji');

        // 9. Panitia also deletes -> archive record is purged completely
        $this->actingAs($panitia)->delete("/daftar-nilai/{$archive->id}")->assertRedirect('/daftar-nilai');
        $this->assertNull(ExamScoreArchive::find($archive->id));
    }
}

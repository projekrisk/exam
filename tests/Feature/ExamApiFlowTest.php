<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\QuestionExcelImportService;
use App\Services\StudentExcelImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ExamApiFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_login_sync_conflict_free_and_hybrid_grading(): void
    {
        $guru = User::create([
            'username' => '19850101',
            'nip' => '19850101',
            'name' => 'Guru Test',
            'role' => 'guru',
            'password' => User::DEFAULT_TEACHER_PASSWORD,
        ]);

        $subject = Subject::create(['code' => 'MTK', 'name' => 'Matematika']);
        $bank = QuestionBank::create([
            'subject_id' => $subject->id,
            'teacher_id' => $guru->id,
            'title' => 'Bank Test',
            'target_classes' => ['X-1'],
            'is_ready' => true,
        ]);

        $q1 = Question::create([
            'question_bank_id' => $bank->id,
            'type' => 'pg',
            'question_text' => '1+1=?',
            'options' => [
                ['key' => 'A', 'text' => '1', 'is_correct' => false],
                ['key' => 'B', 'text' => '2', 'is_correct' => true],
            ],
            'weight' => 2,
            'order_no' => 1,
        ]);

        $q2 = Question::create([
            'question_bank_id' => $bank->id,
            'type' => 'pg_kompleks',
            'question_text' => 'Genap?',
            'options' => [
                ['key' => 'A', 'text' => '2', 'is_correct' => true],
                ['key' => 'B', 'text' => '3', 'is_correct' => false],
                ['key' => 'C', 'text' => '4', 'is_correct' => true],
            ],
            'scoring_mode' => 'full',
            'weight' => 2,
            'order_no' => 2,
        ]);

        $q3 = Question::create([
            'question_bank_id' => $bank->id,
            'type' => 'benar_salah',
            'question_text' => 'Pernyataan',
            'options' => [
                ['key' => '1', 'statement' => 'Bumi bulat', 'correct' => true],
            ],
            'scoring_mode' => 'full',
            'weight' => 2,
            'order_no' => 3,
        ]);

        $q4 = Question::create([
            'question_bank_id' => $bank->id,
            'type' => 'uraian',
            'question_text' => 'Jelaskan!',
            'weight' => 4,
            'order_no' => 4,
        ]);

        $exam = Exam::create([
            'title' => 'Ujian Test',
            'question_bank_id' => $bank->id,
            'subject_id' => $subject->id,
            'created_by' => $guru->id,
            'execution_mode' => 'mandiri',
            'target_classes' => ['X-1'],
            'start_at' => now()->subMinute(),
            'end_at' => now()->addHour(),
            'duration_minutes' => 60,
            'token' => 'ABC123',
            'require_token' => true,
            'status' => 'active',
            'show_score' => true,
        ]);

        Student::create([
            'nis' => '24001',
            'nisn' => '0081234567',
            'name' => 'Siswa Test',
            'kelas' => 'X-1',
        ]);

        $loginRes = $this->postJson('/api/v1/student/login', [
            'nis' => '24001',
            'nisn' => '0081234567',
            'device_id' => 'dev-001',
        ])->assertOk();

        $token = $loginRes->json('token');

        $startRes = $this->withToken($token)->postJson("/api/v1/student/exams/{$exam->id}/start", [
            'device_id' => 'dev-001',
            'token' => 'ABC123',
        ])->assertOk();

        $attemptId = $startRes->json('attempt.id');
        $this->assertCount(4, $startRes->json('questions'));

        $pgQuestion = collect($startRes->json('questions'))->firstWhere('type', 'pg');
        $this->assertArrayNotHasKey('is_correct', $pgQuestion['options'][0]);

        $this->withToken($token)->postJson("/api/v1/student/exams/{$exam->id}/start", [
            'device_id' => 'dev-999',
            'token' => 'ABC123',
        ])->assertStatus(403);

        $this->withToken($token)->postJson("/api/v1/student/attempts/{$attemptId}/sync", [
            'device_id' => 'dev-001',
            'answers' => [
                ['question_id' => $q1->id, 'answer_data' => ['selected' => 'B'], 'client_updated_at' => 2000],
                ['question_id' => $q2->id, 'answer_data' => ['selected' => ['A', 'C']], 'client_updated_at' => 2000],
            ],
        ])->assertOk();

        $this->withToken($token)->postJson("/api/v1/student/attempts/{$attemptId}/sync", [
            'device_id' => 'dev-001',
            'answers' => [
                ['question_id' => $q1->id, 'answer_data' => ['selected' => 'A'], 'client_updated_at' => 1000],
            ],
        ])->assertOk();

        $savedQ1 = ExamAnswer::where('attempt_id', $attemptId)->where('question_id', $q1->id)->first();
        $this->assertSame('B', $savedQ1->answer_data['selected']);

        $submitRes = $this->withToken($token)->postJson("/api/v1/student/attempts/{$attemptId}/submit", [
            'device_id' => 'dev-001',
            'answers' => [
                ['question_id' => $q3->id, 'answer_data' => ['statements' => ['1' => true]], 'client_updated_at' => 3000],
                ['question_id' => $q4->id, 'answer_data' => ['text' => 'Ini jawaban uraian'], 'client_updated_at' => 3000],
            ],
        ])->assertOk();

        $this->assertSame('pending_essay', $submitRes->json('grading_status'));
        $this->assertEquals(60.0, $submitRes->json('objective_score'));

        Student::create([
            'nis' => '24099',
            'nisn' => '0089999999',
            'name' => 'Siswa Kelas Lain',
            'kelas' => 'X-2',
        ]);
        $otherLogin = $this->postJson('/api/v1/student/login', [
            'nis' => '24099',
            'nisn' => '0089999999',
            'device_id' => 'dev-x2',
        ])->assertOk();

        $otherToken = $otherLogin->json('token');
        $this->app['auth']->forgetGuards();
        $this->withToken($otherToken)->getJson('/api/v1/student/exams')
            ->assertOk()
            ->assertJsonCount(0, 'exams');

        $this->withToken($otherToken)->postJson("/api/v1/student/exams/{$exam->id}/start", [
            'device_id' => 'dev-x2',
            'token' => 'ABC123',
        ])->assertStatus(403);
    }

    public function test_excel_student_import(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['NIS', 'NISN', 'Nama', 'Kelas', 'Ruang'],
            ['25001', '0091111111', 'Ani', 'X-2', 'Ruang 1'],
            ['25002', '0092222222', 'Budi', 'X-2', 'Lab Komp 2'],
        ]);
        $temp = sys_get_temp_dir() . '/test_students.xlsx';
        (new Xlsx($spreadsheet))->save($temp);

        $file = new UploadedFile($temp, 'test_students.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $result = app(StudentExcelImportService::class)->import($file);

        $this->assertSame(2, $result['imported']);
        $this->assertDatabaseHas('students', ['nis' => '25001', 'nisn' => '0091111111', 'kelas' => 'X-2', 'ruang' => 'Ruang 1']);
        $this->assertDatabaseHas('students', ['nis' => '25002', 'ruang' => 'Lab Komp 2']);

        $spreadsheet2 = new Spreadsheet();
        $spreadsheet2->getActiveSheet()->fromArray([
            ['NIS', 'NISN', 'Nama', 'Kelas', 'Ruang'],
            ['25001', '0091111999', 'Ani Updated', 'XI-1', 'Ruang 3'],
        ]);
        (new Xlsx($spreadsheet2))->save($temp);
        $file2 = new UploadedFile($temp, 'test_students.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        app(StudentExcelImportService::class)->import($file2);

        $this->assertDatabaseCount('students', 2);
        $this->assertDatabaseHas('students', ['nis' => '25001', 'nisn' => '0091111999', 'name' => 'Ani Updated', 'kelas' => 'XI-1', 'ruang' => 'Ruang 3']);
        @unlink($temp);
    }

    public function test_uraian_auto_text_case_insensitive_scoring(): void
    {
        $guru = User::create([
            'username' => '19850102',
            'nip' => '19850102',
            'name' => 'Guru Essay',
            'role' => 'guru',
            'password' => User::DEFAULT_TEACHER_PASSWORD,
        ]);
        $subject = Subject::create(['code' => 'IPS', 'name' => 'IPS']);
        $bank = QuestionBank::create([
            'subject_id' => $subject->id,
            'teacher_id' => $guru->id,
            'title' => 'Bank Essay Auto',
            'target_classes' => ['X-1'],
            'is_ready' => true,
        ]);
        $q = Question::create([
            'question_bank_id' => $bank->id,
            'type' => 'uraian',
            'question_text' => 'Ibu kota Indonesia?',
            'options' => ['mode' => 'auto_text', 'accepted_answers' => ['Jakarta', 'DKI Jakarta']],
            'weight' => 2,
            'order_no' => 1,
        ]);
        $exam = Exam::create([
            'title' => 'Ujian Essay Auto',
            'question_bank_id' => $bank->id,
            'subject_id' => $subject->id,
            'created_by' => $guru->id,
            'execution_mode' => 'mandiri',
            'target_classes' => ['X-1'],
            'start_at' => now()->subMinute(),
            'end_at' => now()->addHour(),
            'duration_minutes' => 30,
            'require_token' => false,
            'status' => 'active',
            'show_score' => true,
        ]);
        Student::create(['nis' => '26001', 'nisn' => '0093333333', 'name' => 'Citra', 'kelas' => 'X-1']);

        $token = $this->postJson('/api/v1/student/login', [
            'nis' => '26001', 'nisn' => '0093333333', 'device_id' => 'dev-u1',
        ])->assertOk()->json('token');

        $startRes = $this->withToken($token)->postJson("/api/v1/student/exams/{$exam->id}/start", [
            'device_id' => 'dev-u1',
        ])->assertOk();

        $this->assertSame('auto_text', $startRes->json('questions.0.uraian_mode'));
        $attemptId = $startRes->json('attempt.id');

        $submitRes = $this->withToken($token)->postJson("/api/v1/student/attempts/{$attemptId}/submit", [
            'device_id' => 'dev-u1',
            'answers' => [
                ['question_id' => $q->id, 'answer_data' => ['text' => '  dKi   jAkArTa '], 'client_updated_at' => 1000],
            ],
        ])->assertOk();

        $this->assertSame('final', $submitRes->json('grading_status'));
        $this->assertEquals(100.0, $submitRes->json('final_score'));
    }

    public function test_excel_question_import(): void
    {
        $guru = User::create([
            'username' => '19850103', 'nip' => '19850103', 'name' => 'Guru Impor',
            'role' => 'guru', 'password' => User::DEFAULT_TEACHER_PASSWORD,
        ]);
        $subject = Subject::create(['code' => 'BIO', 'name' => 'Biologi']);
        $bank = QuestionBank::create([
            'subject_id' => $subject->id, 'teacher_id' => $guru->id,
            'title' => 'Bank Impor', 'target_classes' => ['X-1'], 'is_ready' => false,
        ]);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['Tipe', 'Pertanyaan', 'Bobot', 'Mode_Skor', 'Opsi_A', 'Opsi_B', 'Opsi_C', 'Opsi_D', 'Opsi_E', 'Kunci'],
            ['pg', '2+2=?', 1, 'full', '3', '4', '5', '6', '', 'B'],
            ['pg_kompleks', 'Prima?', 2, 'partial', '2', '4', '3', '', '', 'A,C'],
            ['benar_salah', 'Cek:', 2, 'full', 'Air membeku di 0C', 'Matahari terbit di barat', '', '', '', 'B,S'],
            ['uraian', 'Ibu kota?', 2, 'auto_text', '', '', '', '', '', 'Jakarta, DKI Jakarta'],
        ]);
        $temp = sys_get_temp_dir() . '/test_questions.xlsx';
        (new Xlsx($spreadsheet))->save($temp);

        $file = new UploadedFile($temp, 'test_questions.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $result = app(QuestionExcelImportService::class)->import($file, $bank);

        $this->assertSame(4, $result['imported']);
        $this->assertSame(4, $bank->questions()->count());
        $this->assertDatabaseHas('questions', ['question_bank_id' => $bank->id, 'type' => 'pg_kompleks', 'scoring_mode' => 'partial']);

        $this->actingAs($guru)->get(route('question-banks.show', $bank))->assertOk();

        $panitia = User::create([
            'username' => 'panitia_test', 'name' => 'Panitia Test',
            'role' => 'panitia', 'password' => User::DEFAULT_TEACHER_PASSWORD,
        ]);
        $superAdmin = User::create([
            'username' => 'super_test', 'name' => 'Super Admin Test',
            'role' => 'super_admin', 'password' => User::DEFAULT_TEACHER_PASSWORD,
        ]);

        $this->actingAs($panitia)->get(route('question-banks.index'))->assertOk()->assertDontSee('Bank Impor');
        $this->actingAs($panitia)->get(route('question-banks.show', $bank))->assertStatus(403);

        $this->actingAs($superAdmin)->get(route('question-banks.index'))->assertOk()->assertSee('Bank Impor');
        $this->actingAs($superAdmin)->get(route('question-banks.show', $bank))->assertOk();

        $this->actingAs($guru)->post(route('question-banks.toggle-ready', $bank))->assertRedirect();
        $this->assertTrue($bank->fresh()->is_ready);
        $this->actingAs($panitia)->get(route('question-banks.show', $bank))->assertOk();

        $this->actingAs($panitia)->post(route('question-banks.request-revision', $bank), [
            'revision_note' => 'Nomor 2 opsi kurang lengkap.',
        ])->assertRedirect(route('question-banks.index'));

        $this->assertFalse($bank->fresh()->is_ready);
        $this->assertSame('Nomor 2 opsi kurang lengkap.', $bank->fresh()->revision_note);
        $this->actingAs($guru)->get(route('question-banks.show', $bank))->assertOk()->assertSee('Nomor 2 opsi kurang lengkap.');

        $exam = Exam::create([
            'title' => 'Ujian Terkunci',
            'question_bank_id' => $bank->id,
            'subject_id' => $subject->id,
            'created_by' => $panitia->id,
            'execution_mode' => 'terpusat',
            'target_classes' => ['X-1'],
            'start_at' => now()->subHours(2),
            'end_at' => now()->subMinute(),
            'duration_minutes' => 60,
            'require_token' => false,
            'status' => 'active',
        ]);

        $this->assertTrue($bank->fresh()->isLocked());
        $this->actingAs($guru)->put(route('question-banks.update', $bank), [
            'subject_id' => $subject->id, 'title' => 'Coba Ubah',
        ])->assertStatus(403);
        $this->actingAs($guru)->get(route('exams.monitor', $exam))->assertOk();
        $this->actingAs($guru)->get(route('exams.grade', $exam))->assertRedirect(route('exams.index'));

        $this->actingAs($panitia)->post(route('exams.release-scores', $exam))->assertRedirect();
        $this->assertTrue($exam->fresh()->scores_released);
        $this->actingAs($guru)->get(route('exams.grade', $exam))->assertOk();

        $ongoingExam = Exam::create([
            'title' => 'Ujian Berlangsung',
            'question_bank_id' => $bank->id,
            'subject_id' => $subject->id,
            'created_by' => $panitia->id,
            'execution_mode' => 'terpusat',
            'target_classes' => ['X-1'],
            'start_at' => now()->subMinutes(10),
            'end_at' => now()->addMinutes(50),
            'duration_minutes' => 60,
            'require_token' => false,
            'status' => 'active',
        ]);
        $this->actingAs($panitia)->get(route('exams.edit', $ongoingExam))->assertRedirect(route('exams.index'));
        $this->actingAs($guru)->get(route('exams.grade', $ongoingExam))->assertRedirect(route('exams.index'));
        $this->actingAs($guru)->get(route('exams.export', $ongoingExam))->assertRedirect(route('exams.index'));
        @unlink($temp);
    }
}

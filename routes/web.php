<?php

use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ExamCardController;
use App\Http\Controllers\Web\ExamController;
use App\Http\Controllers\Web\QuestionBankController;
use App\Http\Controllers\Web\ScoreArchiveController;
use App\Http\Controllers\Web\SettingController;
use App\Http\Controllers\Web\StudentController;
use App\Http\Controllers\Web\SubjectController;
use App\Http\Controllers\Web\TeacherController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/uploads/soal/{path}', function (string $path) {
    abort_unless(Storage::disk('public')->exists($path), 404);

    return Storage::disk('public')->response($path);
})->where('path', '.*');

Route::get('/masuk', [AuthWebController::class, 'showLogin'])->name('login');
Route::post('/masuk', [AuthWebController::class, 'login'])->name('login.post');

Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthWebController::class, 'logout'])->name('logout');
    Route::get('/profil', [AuthWebController::class, 'showProfile'])->name('profile.show');
    Route::get('/ganti-password', [AuthWebController::class, 'showChangePassword'])->name('password.change');
    Route::post('/ganti-password', [AuthWebController::class, 'updatePassword'])->name('password.update');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:super_admin,admin,panitia')->group(function () {
        Route::get('/siswa', [StudentController::class, 'index'])->name('students.index');
        Route::get('/guru', [TeacherController::class, 'index'])->name('teachers.index');
        Route::get('/mata-pelajaran', [SubjectController::class, 'index'])->name('subjects.index');

        Route::get('/kartu-ujian', [ExamCardController::class, 'index'])->name('exam-cards.index');
        Route::get('/kartu-ujian/cetak', [ExamCardController::class, 'print'])->name('exam-cards.print');
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::post('/siswa', [StudentController::class, 'store'])->name('students.store');
        Route::put('/siswa/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('/siswa/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
        Route::post('/siswa/impor', [StudentController::class, 'import'])->name('students.import');
        Route::get('/siswa/template', [StudentController::class, 'template'])->name('students.template');

        Route::post('/guru', [TeacherController::class, 'store'])->name('teachers.store');
        Route::put('/guru/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
        Route::post('/guru/{teacher}/reset-password', [TeacherController::class, 'resetPassword'])->name('teachers.reset-password');
        Route::delete('/guru/{teacher}', [TeacherController::class, 'destroy'])->name('teachers.destroy');

        Route::post('/mata-pelajaran', [SubjectController::class, 'store'])->name('subjects.store');
        Route::put('/mata-pelajaran/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
        Route::delete('/mata-pelajaran/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');

        Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
        Route::post('/pengguna', [UserController::class, 'store'])->name('users.store');
        Route::put('/pengguna/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/pengguna/{user}/password', [UserController::class, 'updatePassword'])->name('users.password');
        Route::delete('/pengguna/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/pengaturan', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/pengaturan/instansi', [SettingController::class, 'updateInstansi'])->name('settings.instansi');
        Route::delete('/pengaturan/instansi/logo', [SettingController::class, 'destroyLogo'])->name('settings.logo.destroy');
        Route::post('/pengaturan/kegiatan', [SettingController::class, 'updateKegiatan'])->name('settings.kegiatan');
        Route::post('/pengaturan/app-list', [SettingController::class, 'storeBlacklistedApp'])->name('settings.apps.store');
        Route::post('/pengaturan/app-list/{blacklistedApp}/toggle', [SettingController::class, 'toggleBlacklistedApp'])->name('settings.apps.toggle');
        Route::delete('/pengaturan/app-list/{blacklistedApp}', [SettingController::class, 'destroyBlacklistedApp'])->name('settings.apps.destroy');
        Route::post('/pengaturan/reset-siswa', [SettingController::class, 'resetStudents'])->name('settings.reset-students');
        Route::post('/pengaturan/reset-pengguna', [SettingController::class, 'resetUsers'])->name('settings.reset-users');
    });

    Route::middleware('role:super_admin,admin,panitia,guru')->group(function () {
        Route::get('/bank-soal', [QuestionBankController::class, 'index'])->name('question-banks.index');
        Route::get('/bank-soal/template-impor', [QuestionBankController::class, 'templateQuestions'])->name('question-banks.template');
        Route::post('/bank-soal', [QuestionBankController::class, 'store'])->name('question-banks.store');
        Route::get('/bank-soal/{questionBank}', [QuestionBankController::class, 'show'])->name('question-banks.show');
        Route::get('/bank-soal/{questionBank}/pdf', [QuestionBankController::class, 'exportPdf'])->name('question-banks.export-pdf');
        Route::put('/bank-soal/{questionBank}', [QuestionBankController::class, 'update'])->name('question-banks.update');
        Route::post('/bank-soal/{questionBank}/siap-pakai', [QuestionBankController::class, 'toggleReady'])->name('question-banks.toggle-ready');
        Route::post('/bank-soal/{questionBank}/minta-perbaikan', [QuestionBankController::class, 'requestRevision'])->name('question-banks.request-revision')->middleware('role:super_admin,admin,panitia');
        Route::delete('/bank-soal/{questionBank}', [QuestionBankController::class, 'destroy'])->name('question-banks.destroy');

        Route::post('/bank-soal/{questionBank}/impor-soal', [QuestionBankController::class, 'importQuestions'])->name('questions.import');
        Route::post('/bank-soal/{questionBank}/soal', [QuestionBankController::class, 'storeQuestion'])->name('questions.store');
        Route::put('/bank-soal/{questionBank}/soal/{question}', [QuestionBankController::class, 'updateQuestion'])->name('questions.update');
        Route::delete('/bank-soal/{questionBank}/soal/{question}', [QuestionBankController::class, 'destroyQuestion'])->name('questions.destroy');
    });

    Route::get('/ujian', [ExamController::class, 'index'])->name('exams.index');
    Route::get('/ujian/buat', [ExamController::class, 'create'])->name('exams.create')->middleware('role:super_admin,admin,panitia,guru');
    Route::post('/ujian', [ExamController::class, 'store'])->name('exams.store')->middleware('role:super_admin,admin,panitia,guru');
    Route::get('/ujian/{exam}/ubah', [ExamController::class, 'edit'])->name('exams.edit')->middleware('role:super_admin,admin,panitia,guru');
    Route::put('/ujian/{exam}', [ExamController::class, 'update'])->name('exams.update')->middleware('role:super_admin,admin,panitia,guru');
    Route::delete('/ujian/{exam}', [ExamController::class, 'destroy'])->name('exams.destroy')->middleware('role:super_admin,admin,panitia,guru');
    Route::post('/ujian/{exam}/token-baru', [ExamController::class, 'regenerateToken'])->name('exams.regenerate-token');
    Route::post('/ujian/{exam}/status', [ExamController::class, 'updateStatus'])->name('exams.status');
    Route::post('/ujian/{exam}/rilis-nilai', [ExamController::class, 'releaseScores'])->name('exams.release-scores')->middleware('role:super_admin,admin,panitia');
    Route::get('/ujian/{exam}/pantau', [ExamController::class, 'monitor'])->name('exams.monitor');
    Route::post('/sesi-ujian/{attempt}/reset-perangkat', [ExamController::class, 'resetDevice'])->name('attempts.reset-device');
    Route::post('/sesi-ujian/{attempt}/buka-kunci', [ExamController::class, 'unlockAttempt'])->name('attempts.unlock');
    Route::post('/sesi-ujian/{attempt}/paksa-selesai', [ExamController::class, 'forceSubmit'])->name('attempts.force-submit');

    Route::get('/ujian/{exam}/koreksi', [ExamController::class, 'grade'])->name('exams.grade')->middleware('role:super_admin,admin,panitia,guru');
    Route::post('/sesi-ujian/{attempt}/koreksi', [ExamController::class, 'saveEssayGrade'])->name('attempts.grade')->middleware('role:super_admin,admin,panitia,guru');
    Route::post('/ujian/{exam}/finalisasi-nilai', [ExamController::class, 'finalizeScores'])->name('exams.finalize-scores')->middleware('role:super_admin,admin,panitia,guru');
    Route::get('/ujian/{exam}/ekspor', [ExamController::class, 'exportResults'])->name('exams.export')->middleware('role:super_admin,admin,panitia,guru');

    Route::middleware('role:super_admin,admin,panitia,guru')->group(function () {
        Route::get('/daftar-nilai', [ScoreArchiveController::class, 'index'])->name('score-archives.index');
        Route::get('/daftar-nilai/{scoreArchive}', [ScoreArchiveController::class, 'show'])->name('score-archives.show');
        Route::get('/daftar-nilai/{scoreArchive}/ekspor', [ScoreArchiveController::class, 'export'])->name('score-archives.export');
        Route::get('/daftar-nilai/{scoreArchive}/pdf', [ScoreArchiveController::class, 'pdf'])->name('score-archives.pdf');
        Route::delete('/daftar-nilai/{scoreArchive}', [ScoreArchiveController::class, 'destroy'])->name('score-archives.destroy');
    });
});

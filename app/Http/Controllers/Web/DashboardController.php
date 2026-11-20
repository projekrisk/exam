<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\QuestionBank;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $examQuery = Exam::with(['subject', 'creator'])
            ->when($user->isGuru(), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('created_by', $user->id)
                ->orWhereHas('questionBank', fn ($qb) => $qb->where('teacher_id', $user->id))));

        $activeExams = (clone $examQuery)
            ->where('status', 'active')
            ->where('end_at', '>=', now())
            ->orderBy('start_at')
            ->limit(6)
            ->get()
            ->each->refreshTokenIfNeeded();

        $guruClasses = $user->isGuru() ? $user->allAssignedClasses() : [];
        $studentCount = $user->isGuru()
            ? (empty($guruClasses) ? 0 : Student::where('is_active', true)->whereIn('kelas', $guruClasses)->count())
            : Student::where('is_active', true)->count();

        $stats = [
            'students' => $studentCount,
            'teachers' => User::where('role', 'guru')->count(),
            'banks' => QuestionBank::when($user->isGuru(), fn ($q) => $q->where('teacher_id', $user->id))
                ->when(! $user->isGuru() && ! $user->isSuperAdmin(), fn ($q) => $q->where(fn ($sub) => $sub
                    ->where('is_ready', true)
                    ->orWhere('teacher_id', $user->id)))
                ->count(),
            'active_exams' => (clone $examQuery)->where('status', 'active')->where('end_at', '>=', now())->count(),
            'pending_essays' => ExamAttempt::where('grading_status', 'pending_essay')
                ->when($user->isGuru(), fn ($q) => $q->whereHas('exam.questionBank', fn ($qb) => $qb->where('teacher_id', $user->id)))
                ->count(),
            'active_attempts' => ExamAttempt::where('status', 'in_progress')
                ->when($user->isGuru(), fn ($q) => $q->whereHas('exam', fn ($eq) => $eq
                    ->where('created_by', $user->id)
                    ->orWhereHas('questionBank', fn ($qb) => $qb->where('teacher_id', $user->id))))
                ->count(),
        ];

        $recentAttempts = ExamAttempt::with(['student', 'exam.subject'])
            ->when($user->isGuru(), fn ($q) => $q->whereHas('exam', fn ($eq) => $eq
                ->where('created_by', $user->id)
                ->orWhereHas('questionBank', fn ($qb) => $qb->where('teacher_id', $user->id))))
            ->latest('updated_at')
            ->limit(8)
            ->get();

        return view('dashboard.index', compact('stats', 'activeExams', 'recentAttempts'));
    }
}

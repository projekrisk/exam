<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:32'],
            'nisn' => ['required', 'string', 'max:32'],
            'device_id' => ['required', 'string', 'max:128'],
        ]);

        $student = Student::where('nis', trim($validated['nis']))->first();

        if (! $student || ! hash_equals((string) $student->nisn, trim($validated['nisn']))) {
            return response()->json([
                'message' => 'NIS atau NISN tidak sesuai.',
            ], 401);
        }

        if (! $student->is_active) {
            return response()->json([
                'message' => 'Akun siswa dinonaktifkan. Hubungi panitia.',
            ], 403);
        }

        $student->tokens()->where('name', 'android-exam')->delete();
        $token = $student->createToken('android-exam', ['student'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'server_time_ms' => now()->getTimestampMs(),
            'student' => [
                'id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->name,
                'kelas' => $student->kelas,
                'ruang' => $student->ruang,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }
}

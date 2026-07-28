<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get applications accessible to teacher role
        $applications = Application::where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('slug', ['teacher', 'guru']);
            })
            ->get();

        // Stats & Data for Guidance Students
        $stats = [
            'total_students' => User::where('user_type', 'student')->count(),
            'guided_students' => 24,
            'active_pkl' => 22,
            'pending_reviews' => 5,
        ];

        // Sample list of students assigned to teacher
        $guidedStudents = User::where('user_type', 'student')->take(5)->get();

        return view('teacher.dashboard', compact('user', 'applications', 'stats', 'guidedStudents'));
    }

    public function students()
    {
        $user = Auth::user();
        $students = User::where('user_type', 'student')->paginate(15);

        return view('teacher.students', compact('user', 'students'));
    }

    public function evaluations()
    {
        $user = Auth::user();
        
        $evaluations = [
            ['student_name' => 'Ahmad Rizky', 'nisn' => '0054231890', 'dudi' => 'PT Telkom Indonesia', 'score' => 95, 'status' => 'Selesai'],
            ['student_name' => 'Budi Pratama', 'nisn' => '0054231891', 'dudi' => 'PT Telkom Indonesia', 'score' => 90, 'status' => 'Selesai'],
            ['student_name' => 'Citra Lestari', 'nisn' => '0054231892', 'dudi' => 'CV Digital Creative', 'score' => 88, 'status' => 'Menunggu Verifikasi'],
            ['student_name' => 'Dewi Anggraini', 'nisn' => '0054231893', 'dudi' => 'PT Solusi Informatika', 'score' => null, 'status' => 'Belum Dinilai'],
        ];

        return view('teacher.evaluations', compact('user', 'evaluations'));
    }

    public function apps()
    {
        $user = Auth::user();
        $applications = Application::where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('slug', ['teacher', 'guru']);
            })
            ->get();

        return view('teacher.apps', compact('user', 'applications'));
    }
}

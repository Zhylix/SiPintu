<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TeacherDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get applications accessible to teacher role
        $applications = Application::where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['teacher', 'guru']);
            })
            ->get();

        $stats = [
            'total_students' => User::where('role', 'student')->count(),
            'guided_students' => 24,
        ];

        $guidedStudents = User::where('role', 'student')->take(5)->get();

        return view('teacher.dashboard', compact('user', 'applications', 'stats', 'guidedStudents'));
    }

    public function students()
    {
        $user = Auth::user();
        $students = User::where('role', 'student')->paginate(15);

        return view('teacher.students', compact('user', 'students'));
    }

    public function evaluations()
    {
        $user = Auth::user();

        $evaluations = [
            ['student_name' => 'Ahmad Rizky', 'nisn' => '0054231890', 'dudi' => 'PT Telkom Indonesia', 'score' => 95, 'status' => 'Selesai'],
            ['student_name' => 'Budi Pratama', 'nisn' => '0054231891', 'dudi' => 'PT Telkom Indonesia', 'score' => 90, 'status' => 'Selesai'],
        ];

        return view('teacher.evaluations', compact('user', 'evaluations'));
    }

    public function apps()
    {
        $user = Auth::user();
        $applications = Application::where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['teacher', 'guru']);
            })
            ->get();

        return view('teacher.apps', compact('user', 'applications'));
    }
}

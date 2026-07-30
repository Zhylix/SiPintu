<?php

namespace App\Http\Controllers\Dudi;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DudiDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $applications = Application::with('category')
            ->where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['dudi']);
            })
            ->get();

        $categories = \App\Models\ApplicationCategory::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $favoriteAppIds = $user->favoriteApplications()->pluck('applications.id')->toArray();
        $favoriteApps = $applications->whereIn('id', $favoriteAppIds);

        return view('dudi.dashboard', compact('user', 'applications', 'categories', 'favoriteAppIds', 'favoriteApps'));
    }

    public function evaluations()
    {
        $user = Auth::user();

        $evaluations = [
            ['student_name' => 'Ahmad Rizky', 'division' => 'Network & Software', 'period' => '2026', 'score' => 94, 'status' => 'Selesai'],
            ['student_name' => 'Budi Pratama', 'division' => 'Backend Development', 'period' => '2026', 'score' => 92, 'status' => 'Selesai'],
        ];

        return view('dudi.evaluations', compact('user', 'evaluations'));
    }

    public function apps()
    {
        $user = Auth::user();
        $applications = Application::with('category')
            ->where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['dudi']);
            })
            ->get();

        $categories = \App\Models\ApplicationCategory::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $favoriteAppIds = $user->favoriteApplications()->pluck('applications.id')->toArray();

        return view('dudi.apps', compact('user', 'applications', 'categories', 'favoriteAppIds'));
    }
}

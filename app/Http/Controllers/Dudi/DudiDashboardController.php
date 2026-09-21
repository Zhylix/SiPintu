<?php

namespace App\Http\Controllers\Dudi;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Support\Facades\Auth;

class DudiDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $applications = Application::where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['dudi']);
            })
            ->get();

        $favoriteAppIds = $user->favoriteApplications()->pluck('applications.id')->toArray();
        $favoriteApps = $applications->whereIn('id', $favoriteAppIds);

        return view('dudi.dashboard', compact('user', 'applications', 'favoriteAppIds', 'favoriteApps'));
    }

    public function apps()
    {
        $user = Auth::user();
        $applications = Application::where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['dudi']);
            })
            ->get();

        $favoriteAppIds = $user->favoriteApplications()->pluck('applications.id')->toArray();

        return view('dudi.apps', compact('user', 'applications', 'favoriteAppIds'));
    }
}

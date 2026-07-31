<?php

namespace App\Http\Controllers\Dudi;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationCategory;
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

        $categories = ApplicationCategory::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $favoriteAppIds = $user->favoriteApplications()->pluck('applications.id')->toArray();
        $favoriteApps = $applications->whereIn('id', $favoriteAppIds);

        return view('dudi.dashboard', compact('user', 'applications', 'categories', 'favoriteAppIds', 'favoriteApps'));
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

        $categories = ApplicationCategory::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $favoriteAppIds = $user->favoriteApplications()->pluck('applications.id')->toArray();

        return view('dudi.apps', compact('user', 'applications', 'categories', 'favoriteAppIds'));
    }
}

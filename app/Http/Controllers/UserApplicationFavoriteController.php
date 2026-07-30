<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserApplicationFavoriteController extends Controller
{
    public function toggleFavorite(Request $request, Application $application)
    {
        $user = Auth::user();

        if ($user->hasFavorited($application)) {
            $user->favoriteApplications()->detach($application->id);
            $isFavorited = false;
            $message = "Aplikasi '{$application->name}' dihapus dari daftar favorit.";
        } else {
            $user->favoriteApplications()->attach($application->id);
            $isFavorited = true;
            $message = "Aplikasi '{$application->name}' ditambahkan ke daftar favorit.";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_favorited' => $isFavorited,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }
}

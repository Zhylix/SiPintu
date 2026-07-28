<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PklStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPklController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $statuses = PklStatus::with('student', 'updater')
            ->paginate(15);

        $allowedStatuses = PklStatus::allowedStatuses();

        return view('admin.pkl.index', compact('user', 'statuses', 'allowedStatuses'));
    }
}

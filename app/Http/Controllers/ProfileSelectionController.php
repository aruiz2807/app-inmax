<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileSelectionController extends Controller
{
    /**
     * Show the profile selection screen.
     */
    public function index(Request $request)
    {
        $profileIds = session('login.pending_profiles');

        // If no pending profiles are in session, redirect to standard login
        if (!$profileIds || !is_array($profileIds)) {
            return redirect()->route('login');
        }

        // Fetch matching User models for selection
        $users = User::whereIn('id', $profileIds)->get();

        return view('auth.profile-selection', compact('users'));
    }

    /**
     * Log in as the selected profile.
     */
    public function select(Request $request, int $id)
    {
        $profileIds = session('login.pending_profiles');

        // Ensure the chosen profile ID is one of the validated pending profile IDs
        if (!$profileIds || !in_array($id, $profileIds)) {
            abort(403, 'Unauthorized profile selection.');
        }

        // Programmatically authenticate as the chosen user profile
        Auth::loginUsingId($id, session('login.remember', false));

        // Clean up session state
        session()->forget(['login.pending_profiles', 'login.remember']);

        // Redirect to intended route or default user home dashboard
        return redirect()->intended('/user/home');
    }
}

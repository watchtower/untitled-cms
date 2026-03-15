<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Non-admin profile page — rendered inside PublicLayout.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/UserProfile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update profile for non-admin users.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Deactivate (soft-delete) account for non-admin users.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $this->performAccountDeactivation($request);

        return Redirect::to('/');
    }

    // -------------------------------------------------------------------------
    // Admin profile — rendered inside AuthenticatedLayout
    // -------------------------------------------------------------------------

    /**
     * Admin profile page — rendered inside AuthenticatedLayout.
     */
    public function adminEdit(Request $request): Response
    {
        return Inertia::render('Profile/AdminProfile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update profile for admin users.
     */
    public function adminUpdate(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('admin.profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Deactivate (soft-delete) account for admin users.
     * Blocked if the user is the last administrator in the system.
     */
    public function adminDestroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $adminCount = User::where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('backend_access', true)->where('is_active', true))
            ->count();

        if ($adminCount <= 1) {
            return back()->withErrors([
                'password' => 'You cannot deactivate the only administrator account.',
            ]);
        }

        $this->performAccountDeactivation($request);

        return Redirect::to('/');
    }

    /**
     * Shared account deactivation logic: logout → soft-delete → invalidate session.
     */
    private function performAccountDeactivation(Request $request): void
    {
        $user = $request->user();
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}

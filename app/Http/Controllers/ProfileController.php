<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        
        // Load gaming economy data
        $user->load([
            'subscriptionLevel',
            'userTokens.token', 
            'userCounters.counterType'
        ]);
        
        // Get specific economy data
        $l33tBytesBalance = $user->getL33tBytesBalance();
        $dailyBitsBalance = $user->getDailyBitsBalance();
        $weeklyPowerBitsBalance = $user->getWeeklyPowerBitsBalance();
        
        return view('profile.edit', [
            'user' => $user,
            'l33tBytesBalance' => $l33tBytesBalance,
            'dailyBitsBalance' => $dailyBitsBalance,
            'weeklyPowerBitsBalance' => $weeklyPowerBitsBalance,
        ]);
    }

    /**
     * Update the user's profile information.
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
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        if ($request->user()->hasVerifiedEmail()) {
            $destination = $request->user()->canAccessBackend()
                ? route('admin.dashboard', absolute: false)
                : route('profile.edit', absolute: false);

            return redirect()->intended($destination);
        }

        return Inertia::render('Auth/VerifyEmail', ['status' => session('status')]);
    }
}

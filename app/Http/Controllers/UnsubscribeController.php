<?php

namespace App\Http\Controllers;

use App\Models\SuppressedEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;

class UnsubscribeController extends Controller
{
    /**
     * Handle the unsubscribe request.
     */
    public function __invoke(string $token)
    {
        try {
            // Decrypt the token: email|expiry
            $decrypted = Crypt::decryptString($token);
            [$email, $expiry] = explode('|', $decrypted);

            if (now()->timestamp > (int) $expiry) {
                return Inertia::render('Public/Unsubscribed', ['error' => 'Link has expired.']);
            }

            SuppressedEmail::updateOrCreate(
                ['email' => strtolower($email)],
                ['reason' => 'unsubscribed']
            );

            return Inertia::render('Public/Unsubscribed', ['email' => $email]);
        } catch (\Exception $e) {
            return abort(403, 'Invalid unsubscribe link.');
        }
    }
}

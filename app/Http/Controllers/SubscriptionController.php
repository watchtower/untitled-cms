<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $subscriptionName = $user->getSubscriptionLevelName();
        $tokenBalance = $user->getPermanentTokenBalance();
        $creditBalance = $user->getMonthlyCreditsBalance();

        return view('subscription.index', compact(
            'user',
            'subscriptionName',
            'tokenBalance',
            'creditBalance'
        ));
    }
}

<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public static function log(string $action, string $description, $subject = null, ?array $beforeState = null, bool $isAiAction = false)
    {
        try {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'description' => $description,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id' => $subject ? $subject->id : null,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'before_state' => $beforeState,
                'is_ai_action' => $isAiAction,
            ]);
        } catch (\Exception $e) {
            // Fail silently to not disrupt user flow
            Log::error('Failed to log activity: '.$e->getMessage());
        }
    }
}

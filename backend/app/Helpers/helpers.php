<?php

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

if (!function_exists('activity_log')) {
    /**
     * Create an activity log record.
     *
     * @param string $moduleCode
     * @param string $action
     * @param string $description
     * @param object|null $user
     * @param object|null $subject
     * @param array|null $oldValues
     * @param array|null $newValues
     * @return void
     */
    function activity_log(
        string $moduleCode,
        string $action,
        string $description,
        ?object $user = null,
        ?object $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        try {
            $userId = null;
            if ($user) {
                $userId = $user->id;
            } elseif (request()->has('current_user')) {
                $userId = request()->get('current_user')->id;
            }

            ActivityLog::create([
                'user_id' => $userId,
                'module_code' => $moduleCode,
                'action' => $action,
                'description' => $description,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id' => $subject ? $subject->id : null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'url' => request()->fullUrl(),
                'method' => request()->method()
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed creating activity log: " . $e->getMessage());
        }
    }
}

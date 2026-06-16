<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    /**
     * Display a listing of activity logs.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 20);

        $logs = ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $formattedLogs = $logs->getCollection()->map(function ($log) {
            return [
                'id' => $log->id,
                'username' => $log->user ? $log->user->username : 'Sistem',
                'user_name' => $log->user ? $log->user->name : 'Sistem',
                'module' => ucfirst($log->module_code),
                'action' => strtoupper($log->action),
                'description' => $log->description,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->format('d/m/Y H:i:s'),
            ];
        });

        // Replace collection in paginator
        $logs->setCollection($formattedLogs);

        return response()->json($logs);
    }
}

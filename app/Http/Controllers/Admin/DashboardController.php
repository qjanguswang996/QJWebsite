<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParsedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Query database for actual metrics
        $totalFiles = ParsedFile::count();
        $stlCount = ParsedFile::where('format', 'STL')->count();
        $threeMfCount = ParsedFile::where('format', '3MF')->count();
        $totalSizeBytes = ParsedFile::sum('file_size');

        $formattedTotalSize = $this->formatBytes($totalSizeBytes);

        // Fetch recent parsed files from database
        $recentFiles = ParsedFile::latest('created_at')->take(5)->get();

        // System status calculations
        $systemStatus = [
            'storage_driver' => config('filesystems.default', 'local'),
            'php_version' => PHP_VERSION,
            'database_status' => '已連線 (' . config('database.default') . ')',
            'queue_connection' => config('queue.default', 'sync'),
            'webgl_status' => 'Three.js v0.186 (準備就緒)',
        ];

        // Active state tab for UI state demonstrations
        $activeDemoState = $request->query('demo_state', 'normal');

        return view('admin.dashboard', compact(
            'totalFiles',
            'stlCount',
            'threeMfCount',
            'formattedTotalSize',
            'recentFiles',
            'systemStatus',
            'activeDemoState'
        ));
    }

    private function formatBytes($bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}

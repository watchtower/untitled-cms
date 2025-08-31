<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteMonitor;
use App\Models\User;
use App\Services\SiteMonitorService;
use Illuminate\Http\Request;

class SiteMonitorController extends Controller
{
    private SiteMonitorService $monitorService;

    public function __construct(SiteMonitorService $monitorService)
    {
        $this->middleware(['auth', 'verified']);
        $this->monitorService = $monitorService;
    }

    /**
     * Display a listing of site monitors
     */
    public function index(Request $request)
    {
        $query = SiteMonitor::with(['user.subscriptionLevel'])
            ->orderBy('created_at', 'desc');

        // Filter by user if specified
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by status if specified
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $monitors = $query->paginate(20);
        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        return view('admin.site-monitors.index', compact('monitors', 'users'));
    }

    /**
     * Show the form for creating a new site monitor
     */
    public function create()
    {
        $users = User::select('id', 'name', 'email')->orderBy('name')->get();
        return view('admin.site-monitors.create', compact('users'));
    }

    /**
     * Store a newly created site monitor
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'check_interval_minutes' => 'integer|min:1|max:60',
            'notifications_enabled' => 'boolean',
        ]);

        try {
            $user = User::findOrFail($validated['user_id']);
            $monitor = $this->monitorService->createMonitor($user, $validated);

            return redirect()
                ->route('admin.site-monitors.index')
                ->with('success', "Site monitor '{$monitor->name}' created successfully.");

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified site monitor
     */
    public function show(SiteMonitor $siteMonitor)
    {
        $siteMonitor->load(['user.subscriptionLevel']);
        
        // Get recent check history (simplified - would typically be in separate table)
        $recentChecks = [];

        return view('admin.site-monitors.show', compact('siteMonitor', 'recentChecks'));
    }

    /**
     * Show the form for editing the specified site monitor
     */
    public function edit(SiteMonitor $siteMonitor)
    {
        return view('admin.site-monitors.edit', compact('siteMonitor'));
    }

    /**
     * Update the specified site monitor
     */
    public function update(Request $request, SiteMonitor $siteMonitor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'check_interval_minutes' => 'integer|min:1|max:60',
            'notifications_enabled' => 'boolean',
            'status' => 'in:active,inactive,failed',
        ]);

        $siteMonitor->update($validated);

        return redirect()
            ->route('admin.site-monitors.index')
            ->with('success', "Site monitor '{$siteMonitor->name}' updated successfully.");
    }

    /**
     * Remove the specified site monitor
     */
    public function destroy(SiteMonitor $siteMonitor)
    {
        $name = $siteMonitor->name;
        $siteMonitor->delete();

        return redirect()
            ->route('admin.site-monitors.index')
            ->with('success', "Site monitor '{$name}' deleted successfully.");
    }

    /**
     * Manually trigger a check for the specified monitor
     */
    public function check(SiteMonitor $siteMonitor)
    {
        try {
            $result = $this->monitorService->checkMonitor($siteMonitor);

            if ($result['success']) {
                $message = "Monitor check completed successfully. Response time: {$result['response_time']}ms";
            } else {
                $message = "Monitor check failed. " . ($result['error'] ?? "Status code: {$result['status_code']}");
            }

            return back()->with($result['success'] ? 'success' : 'warning', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to check monitor: ' . $e->getMessage());
        }
    }
}

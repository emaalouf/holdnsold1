<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Auction;
use App\Models\Report;
use Illuminate\Http\Request;
use App\Http\Resources\ReportResource;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function reportAuction(Request $request, Auction $auction)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
        ]);

        // Check if user has already reported this auction
        $existingReport = Report::where([
            'reporter_id' => $request->user()->id,
            'reportable_type' => Auction::class,
            'reportable_id' => $auction->id,
            'status' => 'pending'
        ])->first();

        if ($existingReport) {
            throw ValidationException::withMessages([
                'auction' => ['You have already reported this auction.']
            ]);
        }

        $report = Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => Auction::class,
            'reportable_id' => $auction->id,
            'reason' => $validated['reason'],
            'description' => $validated['description']
        ]);

        // Notify admins about new report
        // You can implement this using your notification system

        return new ReportResource($report);
    }

    public function reportUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
        ]);

        // Prevent self-reporting
        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages([
                'user' => ['You cannot report yourself.']
            ]);
        }

        // Check if user has already reported this user
        $existingReport = Report::where([
            'reporter_id' => $request->user()->id,
            'reportable_type' => User::class,
            'reportable_id' => $user->id,
            'status' => 'pending'
        ])->first();

        if ($existingReport) {
            throw ValidationException::withMessages([
                'user' => ['You have already reported this user.']
            ]);
        }

        $report = Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => User::class,
            'reportable_id' => $user->id,
            'reason' => $validated['reason'],
            'description' => $validated['description']
        ]);

        // Notify admins about new report
        // You can implement this using your notification system

        return new ReportResource($report);
    }

    // Admin methods
    public function index(Request $request)
    {
        $this->authorize('viewAny', Report::class);

        $reports = Report::with(['reporter', 'reportable', 'resolver'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->type, function ($query, $type) {
                return $query->where('reportable_type', $type);
            })
            ->latest()
            ->paginate();

        return ReportResource::collection($reports);
    }

    public function update(Request $request, Report $report)
    {
        $this->authorize('update', $report);

        $validated = $request->validate([
            'status' => 'required|in:investigating,resolved,dismissed',
            'admin_notes' => 'required|string|max:1000',
        ]);

        $report->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'],
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        // Notify reporter about status update
        // You can implement this using your notification system

        return new ReportResource($report->load(['reporter', 'reportable', 'resolver']));
    }
} 
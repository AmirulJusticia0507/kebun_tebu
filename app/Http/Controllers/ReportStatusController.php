<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ReportStatusController extends Controller
{
    public function update(Request $request, Report $report)
    {
        Gate::authorize('update', $report);

        $validated = $request->validate([
            'status'     => 'required|in:OPEN,ON_PROGRESS,CLOSED',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $data = [
            'status'     => $validated['status'],
            'admin_note' => $validated['admin_note'] ?? $report->admin_note,
            'handled_by' => Auth::id(),
        ];

        if ($validated['status'] === 'CLOSED' && !$report->isClosed()) {
            $data['resolved_at'] = now();
        }

        $report->update($data);

        Notification::create([
            'type' => 'report.status_changed',
            'notifiable_type' => User::class,
            'notifiable_id' => $report->user_id,
            'data' => [
                'title' => 'Status laporan berubah',
                'message' => "{$report->title}: {$validated['status']}",
                'report_id' => $report->id,
                'status' => $validated['status'],
                'url' => route('reports.show', $report),
            ],
            'channel' => 'database',
            'sent_at' => now(),
        ]);

        return back()->with('success', 'Status laporan berhasil diperbarui.');
    }
}

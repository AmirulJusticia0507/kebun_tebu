<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;

class ReportStatusController extends Controller
{
    public function update(Request $request, Report $report)
    {
        $this->authorize('update', $report);

        $validated = $request->validate([
            'status'     => 'required|in:OPEN,ON_PROGRESS,CLOSED',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $data = [
            'status'     => $validated['status'],
            'admin_note' => $validated['admin_note'] ?? $report->admin_note,
            'handled_by' => auth()->id(),
        ];

        if ($validated['status'] === 'CLOSED' && !$report->isClosed()) {
            $data['resolved_at'] = now();
        }

        $report->update($data);

        return back()->with('success', 'Status laporan berhasil diperbarui.');
    }
}

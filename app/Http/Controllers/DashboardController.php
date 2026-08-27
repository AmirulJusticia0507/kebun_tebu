<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $totalReports    = Report::count();
        $openReports     = Report::where('status', 'OPEN')->count();
        $progressReports = Report::where('status', 'ON_PROGRESS')->count();
        $closedReports   = Report::where('status', 'CLOSED')->count();

        $byCategory = Report::join('categories', 'reports.category_id', '=', 'categories.id')
            ->selectRaw('categories.id as category_id, categories.name, categories.color_code, COUNT(*) as count')
            ->groupBy('categories.id', 'categories.name', 'categories.color_code')
            ->orderByDesc('count')
            ->get();

        $recentReports = Report::with(['user', 'category', 'block'])
            ->orderByDesc('reported_at')
            ->limit(10)
            ->get();

        return Inertia::render('Dashboard', [
            'user'          => Auth::user(),
            'stats'         => [
                'total_reports'    => $totalReports,
                'open_reports'     => $openReports,
                'progress_reports' => $progressReports,
                'closed_reports'   => $closedReports,
                'by_category'      => $byCategory,
            ],
            'recentReports' => $recentReports,
        ]);
    }
}

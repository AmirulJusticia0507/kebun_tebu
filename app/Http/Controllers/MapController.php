<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Category;
use App\Models\Report;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MapController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::with(['user', 'category', 'block'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('block_id')) {
            $query->where('block_id', $request->block_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('reported_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('reported_at', '<=', $request->date_to);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reports = $query->orderByDesc('reported_at')->get();
        $categories = Category::all();
        $blocks = Block::with('pic')->where('is_active', true)->get();

        return Inertia::render('Map/Index', [
            'user'       => auth()->user(),
            'reports'    => $reports,
            'categories' => $categories,
            'blocks'     => $blocks,
            'filters'    => $request->only(['category_id', 'block_id', 'date_from', 'date_to', 'status']),
        ]);
    }
}

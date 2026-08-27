<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Category;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Report::with(['category', 'block', 'user'])
            ->orderByDesc('reported_at');

        // Field officers only see their own reports
        if ($user->role === 'field_officer') {
            $query->where('user_id', $user->id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search by title
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $reports = $query->paginate(15)->withQueryString();

        return Inertia::render('Reports/Index', [
            'user'       => $user,
            'reports'    => $reports,
            'categories' => Category::select('id', 'name', 'color_code')->get(),
            'filters'    => $request->only(['status', 'category_id', 'search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Reports/Create', [
            'user'       => Auth::user(),
            'categories' => Category::all(),
            'blocks'     => Block::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'             => 'required|string|max:150',
            'category_id'       => 'required|exists:categories,id',
            'block_id'          => 'nullable|exists:blocks,id',
            'block_code'        => 'nullable|string|max:50',
            'description'       => 'nullable|string',
            'latitude'          => 'required|numeric|between:-90,90',
            'longitude'         => 'required|numeric|between:-180,180',
            'photo'             => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'checklist_answers' => 'nullable|array',
        ]);

        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('reports/photos', 'public');
            $photoUrl = Storage::url($path);
        }

        $category = Category::find($validated['category_id']);

        Report::create([
            'user_id'           => Auth::id(),
            'category_id'       => $validated['category_id'],
            'block_id'          => $validated['block_id'] ?? null,
            'block_code'        => $validated['block_code'] ?? null,
            'title'             => $validated['title'],
            'description'       => $validated['description'] ?? null,
            'latitude'          => $validated['latitude'],
            'longitude'         => $validated['longitude'],
            'photo_url'         => $photoUrl,
            'status'            => 'OPEN',
            'reported_at'       => now(),
            'checklist_answers' => $validated['checklist_answers'] ?? null,
            'sla_deadline'      => $category?->sla_hours
                ? now()->addHours($category->sla_hours)
                : null,
        ]);

        return redirect()->route('map')->with('success', 'Laporan berhasil dikirim!');
    }

    public function show(Report $report)
    {
        $report->load(['user', 'category', 'block', 'handler']);

        return Inertia::render('Reports/Show', [
            'user'   => Auth::user(),
            'report' => $report,
        ]);
    }

    public function sync(Request $request)
    {
        $validated = $request->validate([
            'drafts'               => 'required|array',
            'drafts.*.title'       => 'required|string|max:150',
            'drafts.*.category_id' => 'required|exists:categories,id',
            'drafts.*.latitude'    => 'required|numeric',
            'drafts.*.longitude'   => 'required|numeric',
        ]);

        $createdCount = 0;
        foreach ($validated['drafts'] as $item) {
            $category = Category::find($item['category_id']);
            Report::create([
                'user_id'           => Auth::id(),
                'category_id'       => $item['category_id'],
                'block_id'          => $item['block_id'] ?? null,
                'title'             => $item['title'],
                'description'       => $item['description'] ?? null,
                'latitude'          => $item['latitude'],
                'longitude'         => $item['longitude'],
                'status'            => 'OPEN',
                'reported_at'       => $item['created_at'] ?? now(),
                'checklist_answers' => $item['checklist_answers'] ?? null,
                'sla_deadline'      => $category?->sla_hours ? now()->addHours($category->sla_hours) : null,
            ]);
            $createdCount++;
        }

        return response()->json([
            'message' => "{$createdCount} laporan offline berhasil disinkronkan.",
            'count'   => $createdCount,
        ]);
    }

    public function exportGeoJson(Request $request)
    {
        $reports = Report::with(['category', 'block', 'user'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $features = $reports->map(fn($r) => [
            'type'       => 'Feature',
            'properties' => [
                'id'          => $r->id,
                'title'       => $r->title,
                'category'    => $r->category?->name,
                'status'      => $r->status,
                'reporter'    => $r->user?->name,
                'reported_at' => $r->reported_at?->toIso8601String(),
            ],
            'geometry'   => [
                'type'        => 'Point',
                'coordinates' => [(float)$r->longitude, (float)$r->latitude],
            ],
        ]);

        return response()->json([
            'type'     => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    public function exportCsv(Request $request)
    {
        $reports = Report::with(['category', 'block', 'user'])->get();

        $filename = 'laporan_kebun_tebu_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($reports) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Judul', 'Kategori', 'Blok', 'Pelapor', 'Status', 'Latitude', 'Longitude', 'Waktu Laporan']);

            foreach ($reports as $r) {
                fputcsv($file, [
                    $r->id,
                    $r->title,
                    $r->category?->name ?? '-',
                    $r->block_code ?? $r->block?->code ?? '-',
                    $r->user?->name ?? '-',
                    $r->status,
                    $r->latitude,
                    $r->longitude,
                    $r->reported_at ? $r->reported_at->format('Y-m-d H:i:s') : '-',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Category;
use App\Models\Notification;
use App\Models\Report;
use App\Models\User;
use App\Jobs\SendWhatsAppNotification;
use App\Jobs\SendWebPushNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['OPEN', 'ON_PROGRESS', 'CLOSED'])],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

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
            'filters'    => $filters,
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
            'client_uuid'       => 'nullable|uuid',
            'category_id'       => 'required|exists:categories,id',
            'block_id'          => 'nullable|exists:blocks,id',
            'block_code'        => 'nullable|string|max:50',
            'description'       => 'nullable|string',
            'latitude'          => 'required|numeric|between:-90,90',
            'longitude'         => 'required|numeric|between:-180,180',
            'photo'             => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120|dimensions:max_width=8000,max_height=8000',
            'checklist_answers' => 'nullable|array',
        ]);

        if (!empty($validated['client_uuid'])) {
            $existing = Report::where('user_id', Auth::id())
                ->where('client_uuid', $validated['client_uuid'])
                ->first();

            if ($existing) {
                return redirect()->route('map')->with('success', 'Laporan sudah tersinkronisasi.');
            }
        }

        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $photoUrl = $this->storeSanitizedPhoto($request->file('photo'));
        }

        $category = Category::find($validated['category_id']);

        $report = Report::create([
            'user_id'           => Auth::id(),
            'client_uuid'       => $validated['client_uuid'] ?? null,
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

        $this->notifyAdminsAboutNewReport($report);

        return redirect()->route('map')->with('success', 'Laporan berhasil dikirim!');
    }

    private function notifyAdminsAboutNewReport(Report $report): void
    {
        User::where('role', 'admin')->each(function (User $admin) use ($report) {
            Notification::create([
                'type' => 'report.created',
                'notifiable_type' => User::class,
                'notifiable_id' => $admin->id,
                'data' => [
                    'title' => 'Laporan baru',
                    'message' => $report->title,
                    'report_id' => $report->id,
                    'url' => route('reports.show', $report),
                ],
                'channel' => 'database',
                'sent_at' => now(),
            ]);

            SendWhatsAppNotification::dispatch(
                $admin->id,
                'report.created',
                "Laporan baru: {$report->title}",
                ['report_id' => $report->id],
            );
            SendWebPushNotification::dispatch(
                $admin->id,
                'report.created',
                'Laporan baru',
                $report->title,
                ['report_id' => $report->id, 'url' => route('reports.show', $report)],
            );
        });
    }

    private function storeSanitizedPhoto(\Illuminate\Http\UploadedFile $photo): string
    {
        $image = @imagecreatefromstring(file_get_contents($photo->getRealPath()));
        if (! $image) {
            throw ValidationException::withMessages(['photo' => 'Foto tidak dapat diproses.']);
        }

        if ($photo->getMimeType() === 'image/jpeg' && function_exists('exif_read_data')) {
            $orientation = @exif_read_data($photo->getRealPath())['Orientation'] ?? 1;
            $image = match ($orientation) {
                3 => imagerotate($image, 180, 0),
                6 => imagerotate($image, -90, 0),
                8 => imagerotate($image, 90, 0),
                default => $image,
            };
        }

        ob_start();
        imagewebp($image, null, 82);
        $contents = ob_get_clean();
        imagedestroy($image);

        $path = 'reports/photos/' . Str::uuid() . '.webp';
        Storage::disk('public')->put($path, $contents);

        return Storage::url($path);
    }

    public function show(Report $report)
    {
        Gate::authorize('view', $report);
        $report->load(['user', 'category', 'block', 'handler']);

        return Inertia::render('Reports/Show', [
            'user'   => Auth::user(),
            'report' => $report,
        ]);
    }

    public function sync(Request $request)
    {
        $validated = $request->validate([
            'drafts'               => 'required|array|min:1|max:50',
            'drafts.*.title'       => 'required|string|max:150',
            'drafts.*.client_uuid' => 'required|uuid|distinct',
            'drafts.*.category_id' => 'required|exists:categories,id',
            'drafts.*.block_id'    => 'nullable|exists:blocks,id',
            'drafts.*.block_code'  => 'nullable|string|max:50',
            'drafts.*.description' => 'nullable|string',
            'drafts.*.latitude'    => 'required|numeric|between:-90,90',
            'drafts.*.longitude'   => 'required|numeric|between:-180,180',
            'drafts.*.created_at'  => 'nullable|date|before_or_equal:now',
            'drafts.*.checklist_answers' => 'nullable|array',
        ]);

        $createdReports = DB::transaction(function () use ($validated) {
            $created = collect();

            foreach ($validated['drafts'] as $item) {
                $category = Category::findOrFail($item['category_id']);
                $reportedAt = isset($item['created_at']) ? Carbon::parse($item['created_at']) : now();
                $report = Report::firstOrCreate(
                    ['user_id' => Auth::id(), 'client_uuid' => $item['client_uuid']],
                    [
                        'category_id' => $item['category_id'],
                        'block_id' => $item['block_id'] ?? null,
                        'block_code' => $item['block_code'] ?? null,
                        'title' => $item['title'],
                        'description' => $item['description'] ?? null,
                        'latitude' => $item['latitude'],
                        'longitude' => $item['longitude'],
                        'status' => 'OPEN',
                        'reported_at' => $reportedAt,
                        'checklist_answers' => $item['checklist_answers'] ?? null,
                        'sla_deadline' => $category->sla_hours ? $reportedAt->copy()->addHours($category->sla_hours) : null,
                    ],
                );

                if ($report->wasRecentlyCreated) {
                    $created->push($report);
                }
            }

            return $created;
        });

        $createdReports->each(fn (Report $report) => $this->notifyAdminsAboutNewReport($report));
        $createdCount = $createdReports->count();
        $duplicateCount = count($validated['drafts']) - $createdCount;

        return response()->json([
            'message' => "{$createdCount} laporan offline berhasil disinkronkan.",
            'created_count' => $createdCount,
            'duplicate_count' => $duplicateCount,
        ]);
    }

    public function exportGeoJson(Request $request)
    {
        $reports = $this->exportQuery($request)
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
        $reports = $this->exportQuery($request)->get();

        $filename = 'laporan_kebun_tebu_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($reports) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Judul', 'Kategori', 'Blok', 'Pelapor', 'Status', 'Latitude', 'Longitude', 'Waktu Laporan']);

            foreach ($reports as $r) {
                $safe = fn ($value) => is_string($value) && preg_match('/^[=+\-@]/', $value)
                    ? "'{$value}"
                    : $value;

                fputcsv($file, array_map($safe, [
                    $r->id,
                    $r->title,
                    $r->category?->name ?? '-',
                    $r->block_code ?? $r->block?->code ?? '-',
                    $r->user?->name ?? '-',
                    $r->status,
                    $r->latitude,
                    $r->longitude,
                    $r->reported_at ? $r->reported_at->format('Y-m-d H:i:s') : '-',
                ]));
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportQuery(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['OPEN', 'ON_PROGRESS', 'CLOSED'])],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'block_id' => ['nullable', 'integer', 'exists:blocks,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        return Report::with(['category', 'block', 'user'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['category_id'] ?? null, fn ($query, $category) => $query->where('category_id', $category))
            ->when($filters['block_id'] ?? null, fn ($query, $block) => $query->where('block_id', $block))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('reported_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('reported_at', '<=', $date))
            ->orderByDesc('reported_at');
    }
}

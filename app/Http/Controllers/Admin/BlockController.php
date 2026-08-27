<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BlockController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard/Blocks/Index', [
            'user'   => Auth::user(),
            'blocks' => Block::with('pic')->withCount('reports')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'        => 'required|string|max:20|unique:blocks,code',
            'name'        => 'required|string|max:100',
            'hectare'     => 'nullable|numeric|min:0',
            'pic_user_id' => 'nullable|exists:users,id',
            'polygon'     => 'nullable|array',
            'is_active'   => 'boolean',
        ]);

        Block::create($validated);

        return redirect()->route('admin.blocks.index')->with('success', 'Blok berhasil ditambahkan.');
    }

    public function update(Request $request, Block $block)
    {
        $validated = $request->validate([
            'code'        => "required|string|max:20|unique:blocks,code,{$block->id}",
            'name'        => 'required|string|max:100',
            'hectare'     => 'nullable|numeric|min:0',
            'pic_user_id' => 'nullable|exists:users,id',
            'polygon'     => 'nullable|array',
            'is_active'   => 'boolean',
        ]);

        $block->update($validated);

        return redirect()->route('admin.blocks.index')->with('success', 'Blok berhasil diperbarui.');
    }

    public function destroy(Block $block)
    {
        $block->update(['is_active' => false]);

        return redirect()->route('admin.blocks.index')->with('success', 'Blok berhasil dinonaktifkan.');
    }

    public function geojson()
    {
        $blocks = Block::with('pic')->where('is_active', true)->whereNotNull('polygon')->get();

        $features = $blocks->map(fn($block) => [
            'type'       => 'Feature',
            'properties' => [
                'id'      => $block->id,
                'code'    => $block->code,
                'name'    => $block->name,
                'hectare' => $block->hectare,
                'pic'     => $block->pic?->name,
            ],
            'geometry'   => $block->polygon,
        ]);

        return response()->json([
            'type'     => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard/Categories/Index', [
            'user'       => Auth::user(),
            'categories' => Category::withCount('reports')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:50|unique:categories,name',
            'icon_marker'         => 'nullable|string|max:100',
            'color_code'          => 'required|string|max:10',
            'sla_hours'           => 'nullable|integer|min:1',
            'checklist_template'  => 'nullable|array',
        ]);

        Category::create($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name'                => "required|string|max:50|unique:categories,name,{$category->id}",
            'icon_marker'         => 'nullable|string|max:100',
            'color_code'          => 'required|string|max:10',
            'sla_hours'           => 'nullable|integer|min:1',
            'checklist_template'  => 'nullable|array',
        ]);

        $category->update($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category)
    {
        if ($category->reports()->count() > 0) {
            return back()->with('error', 'Kategori tidak dapat dihapus karena masih memiliki laporan.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil dihapus.');
    }
}

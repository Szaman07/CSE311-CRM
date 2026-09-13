<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Http\Requests\VersionRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        return view('categories.index', ['categories' => Category::withCount('products')->orderBy('name')->orderBy('id')->paginate(min(100, max(1, (int) $request->input('per_page', 20))))]);
    }

    public function store(CategoryRequest $request, CategoryService $service): RedirectResponse
    {
        $service->create($request->user(), $request->validated('name'));

        return back()->with('success', 'Category created.');
    }

    public function update(CategoryRequest $request, Category $category, CategoryService $service): RedirectResponse
    {
        $service->update($request->user(), $category, $request->validated('name'), (int) $request->validated('expected_version'));

        return back()->with('success', 'Category updated.');
    }

    public function archive(VersionRequest $request, Category $category, CategoryService $service): RedirectResponse
    {
        $service->setArchived($request->user(), $category, true, (int) $request->validated('expected_version'));

        return back()->with('success', 'Category archived.');
    }

    public function restore(VersionRequest $request, Category $category, CategoryService $service): RedirectResponse
    {
        $service->setArchived($request->user(), $category, false, (int) $request->validated('expected_version'));

        return back()->with('success', 'Category restored.');
    }
}

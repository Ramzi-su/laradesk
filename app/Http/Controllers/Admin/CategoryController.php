<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::withCount('tickets')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Category::class);

        return view('admin.categories.form', ['category' => new Category]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return to_route('admin.categories.index')->with('success', __('Category created.'));
    }

    public function edit(Category $category): View
    {
        Gate::authorize('update', $category);

        return view('admin.categories.form', ['category' => $category]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return to_route('admin.categories.index')->with('success', __('Category updated.'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        // Soft-deleted tickets still reference the category (and the foreign key restricts deletion).
        if ($category->tickets()->withTrashed()->exists()) {
            return back()->with('error', __('This category still has tickets and cannot be deleted.'));
        }

        $category->delete();

        return to_route('admin.categories.index')->with('success', __('Category deleted.'));
    }
}

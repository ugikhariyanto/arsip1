<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('categories.index', [
            'categories' => Category::query()->withCount('inventoryItems')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('categories.form', [
            'category' => new Category,
            'title' => 'Tambah kategori',
            'submitLabel' => 'Simpan kategori',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validatedData($request));

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category): View
    {
        return view('categories.form', [
            'category' => $category,
            'title' => 'Ubah kategori',
            'submitLabel' => 'Simpan perubahan',
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validatedData($request, $category);
        $previousCode = $category->code;

        DB::transaction(function () use ($category, $data, $previousCode) {
            $category->update($data);

            if ($previousCode !== $category->code) {
                $category->inventoryItems()
                    ->whereNotNull('item_number')
                    ->get()
                    ->each(function ($item) use ($category) {
                        $item->update(['code' => $category->code.'-'.$item->item_number]);
                    });
            }

            $category->inventoryItems()->update(['category' => $category->name]);
        });

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->inventoryItems()->exists()) {
            return redirect()->route('categories.index')
                ->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh barang.');
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus.');
    }

    private function validatedData(Request $request, ?Category $category = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category)],
            'code' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]{2,10}$/', Rule::unique('categories', 'code')->ignore($category)],
        ]);

        if ($category && $data['code'] !== $category->code) {
            $itemsToUpdate = $category->inventoryItems()->whereNotNull('item_number')->get(['id', 'item_number']);
            $itemNumbers = $itemsToUpdate->pluck('item_number');
            $generatedCodes = $itemNumbers->map(fn ($number) => $data['code'].'-'.$number);
            $hasCollision = $generatedCodes->unique()->count() !== $generatedCodes->count()
                || DB::table('inventory_items')
                    ->whereIn('code', $generatedCodes)
                    ->whereNotIn('id', $itemsToUpdate->pluck('id'))
                    ->exists();

            if ($hasCollision) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'code' => 'Kode kategori tidak dapat diubah karena akan membuat kode barang menjadi duplikat.',
                ]);
            }
        }

        return $data;
    }
}

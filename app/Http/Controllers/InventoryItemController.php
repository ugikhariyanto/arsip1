<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class InventoryItemController extends Controller
{
    public function dashboard(): View
    {
        $items = InventoryItem::query();

        return view('dashboard', [
            'totalItems' => (clone $items)->count(),
            'consumableCount' => (clone $items)->where('type', InventoryItem::TYPE_CONSUMABLE)->count(),
            'assetCount' => (clone $items)->where('type', InventoryItem::TYPE_ASSET)->count(),
            'totalConsumables' => (clone $items)
                ->where('type', InventoryItem::TYPE_CONSUMABLE)
                ->sum('quantity'),
            'recentItems' => InventoryItem::query()->latest()->take(5)->get(),
        ]);
    }

    public function index(Request $request): View
    {
        $type = $request->query('type');
        $search = trim((string) $request->query('search', ''));

        $items = InventoryItem::query()
            ->when(in_array($type, [InventoryItem::TYPE_CONSUMABLE, InventoryItem::TYPE_ASSET], true), function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('inventory.index', compact('items', 'type', 'search'));
    }

    public function show(InventoryItem $item): View
    {
        return view('inventory.show', compact('item'));
    }

    public function create(Request $request): View
    {
        $type = in_array($request->query('type'), [InventoryItem::TYPE_CONSUMABLE, InventoryItem::TYPE_ASSET], true)
            ? $request->query('type')
            : InventoryItem::TYPE_CONSUMABLE;

        return view('inventory.form', [
            'item' => new InventoryItem([
                'type' => $type,
                'quantity' => $type === InventoryItem::TYPE_ASSET ? 1 : 0,
                'unit' => 'unit',
            ]),
            'categories' => Category::query()->orderBy('name')->get(),
            'title' => 'Tambah barang',
            'submitLabel' => 'Simpan barang',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $photoPath = $request->file('photo')?->store('inventory', 'public');

        if ($request->hasFile('photo') && ! $photoPath) {
            throw new RuntimeException('Foto barang gagal disimpan. Silakan coba lagi.');
        }

        try {
            $item = InventoryItem::create([...$data, 'photo_path' => $photoPath]);
        } catch (Throwable $exception) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }

            throw $exception;
        }

        return redirect()->route('inventory.index', ['type' => $item->type])->with('success', 'Barang berhasil ditambahkan.');
    }

    public function edit(InventoryItem $item): View
    {
        return view('inventory.form', [
            'item' => $item,
            'categories' => Category::query()->orderBy('name')->get(),
            'title' => 'Ubah barang',
            'submitLabel' => 'Simpan perubahan',
        ]);
    }

    public function update(Request $request, InventoryItem $item): RedirectResponse
    {
        $data = $this->validatedData($request, $item);
        $oldPhotoPath = $item->photo_path;
        $newPhotoPath = $request->file('photo')?->store('inventory', 'public');

        if ($request->hasFile('photo') && ! $newPhotoPath) {
            throw new RuntimeException('Foto barang gagal disimpan. Silakan coba lagi.');
        }

        if ($newPhotoPath) {
            $data['photo_path'] = $newPhotoPath;
        } elseif ($request->boolean('remove_photo')) {
            $data['photo_path'] = null;
        }

        try {
            $item->update($data);
        } catch (Throwable $exception) {
            if ($newPhotoPath) {
                Storage::disk('public')->delete($newPhotoPath);
            }

            throw $exception;
        }

        if (($newPhotoPath || $request->boolean('remove_photo')) && $oldPhotoPath) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        return redirect()->route('inventory.index', ['type' => $item->type])->with('success', 'Data barang berhasil diperbarui.');
    }

    public function destroy(InventoryItem $item): RedirectResponse
    {
        $type = $item->type;
        $photoPath = $item->photo_path;
        $item->delete();

        if ($photoPath) {
            Storage::disk('public')->delete($photoPath);
        }

        return redirect()->route('inventory.index', ['type' => $type])->with('success', 'Barang berhasil dihapus.');
    }

    private function validatedData(Request $request, ?InventoryItem $item = null): array
    {
        $price = trim((string) $request->input('price', ''));

        if ($price !== '') {
            $request->merge(['price' => $price]);
        } else {
            $request->merge(['price' => null]);
        }

        $data = $request->validate([
            'type' => ['required', Rule::in([InventoryItem::TYPE_CONSUMABLE, InventoryItem::TYPE_ASSET])],
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'item_number' => [
                'required',
                'string',
                'regex:/^[0-9]{1,10}$/',
                Rule::unique('inventory_items', 'item_number')
                    ->where('category_id', $request->input('category_id'))
                    ->ignore($item),
            ],
            'quantity' => ['required', 'integer', 'min:'.($request->input('type') === InventoryItem::TYPE_ASSET ? 1 : 0)],
            'price' => ['nullable', 'string', 'regex:/^(?:[0-9]+|[0-9]{1,3}(?:\.[0-9]{3})+)$/', 'max:23'],
            'unit' => ['required', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:150'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_photo' => ['nullable', 'boolean'],
            'condition' => ['nullable', 'string', 'max:100'],
            'acquired_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['type'] === InventoryItem::TYPE_CONSUMABLE) {
            $data['condition'] = null;
            $data['acquired_at'] = null;
            $data['price'] = null;
        } elseif ($data['price'] !== null) {
            $data['price'] = (int) str_replace('.', '', $data['price']);
        }

        $category = Category::findOrFail($data['category_id']);
        $code = $category->code.'-'.$data['item_number'];
        validator(['code' => $code], [
            'code' => ['required', 'max:50', Rule::unique('inventory_items', 'code')->ignore($item)],
        ])->validate();

        unset($data['photo'], $data['remove_photo']);

        return [
            ...$data,
            'category' => $category->name,
            'code' => $code,
        ];
    }
}

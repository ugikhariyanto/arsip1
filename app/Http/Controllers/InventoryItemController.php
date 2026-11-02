<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryItem;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

        $item = new InventoryItem([
            'type' => $type,
            'quantity' => $type === InventoryItem::TYPE_ASSET ? 1 : 0,
            'unit' => 'unit',
        ]);
        $categories = Category::query()->orderBy('name')->get();

        return view('inventory.form', [
            'item' => $item,
            'categories' => $categories,
            'nextNumbers' => $this->nextNumbersFor($categories),
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
            $item = $this->createWithNextNumber($data, $photoPath);
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
        $categories = Category::query()->orderBy('name')->get();

        return view('inventory.form', [
            'item' => $item,
            'categories' => $categories,
            'nextNumbers' => $this->nextNumbersFor($categories, $item),
            'title' => 'Ubah barang',
            'submitLabel' => 'Simpan perubahan',
        ]);
    }

    public function update(Request $request, InventoryItem $item): RedirectResponse
    {
        $data = $this->validatedData($request);
        $oldPhotoPath = $item->photo_path;
        $newPhotoPath = $request->file('photo')?->store('inventory', 'public');

        if ($request->hasFile('photo') && ! $newPhotoPath) {
            throw new RuntimeException('Foto barang gagal disimpan. Silakan coba lagi.');
        }

        // Kode barang hanya berubah bila kategori barang ikut diubah.
        $categoryChanged = (int) $data['category_id'] !== (int) $item->category_id;

        if ($categoryChanged) {
            $category = Category::findOrFail($data['category_id']);
            $itemNumber = InventoryItem::nextNumberForCategory($category->id, $item->id);
            $data['item_number'] = $itemNumber;
            $data['code'] = InventoryItem::buildCode($category->code, $itemNumber);
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

    private function validatedData(Request $request): array
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

        unset($data['photo'], $data['remove_photo']);

        return [
            ...$data,
            'category' => $category->name,
        ];
    }

    private function createWithNextNumber(array $data, ?string $photoPath): InventoryItem
    {
        $attempts = 5;

        while (true) {
            try {
                return DB::transaction(function () use ($data, $photoPath) {
                    $category = Category::findOrFail($data['category_id']);
                    $itemNumber = InventoryItem::nextNumberForCategory($category->id);

                    return InventoryItem::create([
                        ...$data,
                        'item_number' => $itemNumber,
                        'code' => InventoryItem::buildCode($category->code, $itemNumber),
                        'photo_path' => $photoPath,
                    ]);
                });
            } catch (QueryException $exception) {
                $attempts--;

                if ($attempts <= 0 || ! $this->isUniqueViolation($exception)) {
                    throw $exception;
                }
            }
        }
    }

    private function nextNumbersFor(Collection $categories, ?InventoryItem $item = null): array
    {
        return $categories->mapWithKeys(fn (Category $category) => [
            $category->id => InventoryItem::nextNumberForCategory($category->id, $item?->id),
        ])->all();
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505'], true)
            || str_contains(strtolower($exception->getMessage()), 'unique');
    }
}

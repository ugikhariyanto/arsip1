<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Location;
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
            'facilityCount' => (clone $items)->where('type', InventoryItem::TYPE_FACILITY)->count(),
            'totalConsumables' => (clone $items)
                ->where('type', InventoryItem::TYPE_CONSUMABLE)
                ->sum('quantity'),
            'recentItems' => InventoryItem::query()->with('locationRecord')->latest()->take(5)->get(),
        ]);
    }

    public function index(Request $request): View
    {
        $type = $request->query('type');
        $condition = $request->query('condition');
        $locationId = $request->query('location_id');
        $search = trim((string) $request->query('search', ''));

        $items = InventoryItem::query()
            ->with(['categoryRecord', 'locationRecord'])
            ->when(in_array($type, InventoryItem::TYPES, true), function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->when(in_array($condition, InventoryItem::CONDITIONS, true), function ($query) use ($condition) {
                $query->where('condition', $condition);
            })
            ->when($locationId !== null && $locationId !== '', function ($query) use ($locationId) {
                $query->where('location_id', (int) $locationId);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('item_number', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('inventory.index', [
            'items' => $items,
            'type' => $type,
            'condition' => $condition,
            'locationId' => $locationId,
            'search' => $search,
            'locations' => Location::query()->orderBy('name')->get(),
        ]);
    }

    public function show(InventoryItem $item): View
    {
        $item->load(['categoryRecord', 'locationRecord']);

        return view('inventory.show', compact('item'));
    }

    public function create(Request $request): View
    {
        $type = in_array($request->query('type'), InventoryItem::TYPES, true)
            ? $request->query('type')
            : InventoryItem::TYPE_CONSUMABLE;

        $item = new InventoryItem([
            'type' => $type,
            'quantity' => $type === InventoryItem::TYPE_CONSUMABLE ? 0 : 1,
            'unit' => 'unit',
            'condition' => InventoryItem::CONDITION_GOOD,
        ]);
        $categories = Category::query()->orderBy('name')->get();
        $locations = Location::query()->orderBy('name')->get();

        return view('inventory.form', [
            'item' => $item,
            'categories' => $categories,
            'locations' => $locations,
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
        $locations = Location::query()->orderBy('name')->get();

        return view('inventory.form', [
            'item' => $item,
            'categories' => $categories,
            'locations' => $locations,
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

        $needsConditionDetail = in_array($request->input('condition') ?: InventoryItem::CONDITION_GOOD, InventoryItem::CONDITION_DETAIL_REQUIRED, true);
        $calibrationDone = $request->input('calibration_status') === InventoryItem::CALIBRATION_DONE
            && $request->input('type') === InventoryItem::TYPE_ASSET;

        $data = $request->validate([
            'type' => ['required', Rule::in(InventoryItem::TYPES)],
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'quantity' => ['required', 'integer', 'min:'.(in_array($request->input('type'), [InventoryItem::TYPE_ASSET, InventoryItem::TYPE_FACILITY], true) ? 1 : 0)],
            'price' => ['nullable', 'string', 'regex:/^(?:[0-9]+|[0-9]{1,3}(?:\.[0-9]{3})+)$/', 'max:23'],
            'unit' => ['required', 'string', 'max:30'],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_photo' => ['nullable', 'boolean'],
            'condition' => ['nullable', Rule::in(InventoryItem::CONDITIONS)],
            'condition_date' => ['nullable', 'date', Rule::requiredIf($needsConditionDetail)],
            'follow_up' => ['nullable', 'string', 'max:2000'],
            'calibration_status' => ['nullable', Rule::in(InventoryItem::CALIBRATION_STATUSES)],
            'calibration_date' => ['nullable', 'date', Rule::requiredIf($calibrationDone)],
            'acquired_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'condition_date.required' => 'Tanggal rusak atau tanggal hilang wajib diisi sesuai keadaan barang.',
            'calibration_date.required' => 'Tanggal kalibrasi wajib diisi karena status kalibrasi sudah dilakukan.',
        ]);

        $data['condition'] = $data['condition'] ?? InventoryItem::CONDITION_GOOD;

        if ($data['type'] === InventoryItem::TYPE_CONSUMABLE) {
            $data['price'] = null;
            $data['acquired_at'] = null;
            $data['location_id'] = null;
            $data['calibration_status'] = null;
            $data['calibration_date'] = null;
        } else {
            $data['price'] = $data['price'] !== null ? (int) str_replace('.', '', $data['price']) : null;

            if ($data['type'] === InventoryItem::TYPE_FACILITY) {
                $data['calibration_status'] = null;
                $data['calibration_date'] = null;
            } elseif (($data['calibration_status'] ?? null) !== InventoryItem::CALIBRATION_DONE) {
                $data['calibration_date'] = null;
            }
        }

        if (! $needsConditionDetail) {
            $data['condition_date'] = null;
            $data['follow_up'] = null;
        }

        if (($data['location_id'] ?? null) !== null) {
            $data['location'] = Location::findOrFail($data['location_id'])->name;
        } else {
            $data['location'] = null;
            $data['location_id'] = null;
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

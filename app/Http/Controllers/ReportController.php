<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Support\ReportExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    private const EXPORT_FORMATS = ['xlsx', 'docx', 'pdf'];

    public function items(Request $request): View
    {
        $filters = $this->filters($request);

        return view('reports.items', [
            'report' => $this->buildItemsReport($request),
            'filters' => $filters,
            'categories' => Category::query()->orderBy('name')->get(),
            'locations' => Location::query()->orderBy('name')->get(),
        ]);
    }

    public function exportItems(Request $request, string $format, ReportExporter $exporter): Response|RedirectResponse
    {
        $this->ensureFormat($format);

        return $exporter->download($format, $this->buildItemsReport($request));
    }

    public function kir(Request $request): View
    {
        $filters = $this->filters($request);
        $location = $this->selectedLocation($request);

        return view('reports.kir', [
            'report' => $this->buildKirReport($request, $location),
            'filters' => $filters,
            'location' => $location,
            'locations' => Location::query()->orderBy('name')->get(),
        ]);
    }

    public function exportKir(Request $request, string $format, ReportExporter $exporter): Response|RedirectResponse
    {
        $this->ensureFormat($format);
        $location = $this->selectedLocation($request);

        if (! $location) {
            return redirect()->route('reports.kir')
                ->with('error', 'Pilih lokasi / ruangan terlebih dahulu sebelum mengekspor KIR.');
        }

        return $exporter->download($format, $this->buildKirReport($request, $location));
    }

    public function conditions(Request $request): View
    {
        $filters = $this->filters($request);

        return view('reports.conditions', [
            'report' => $this->buildConditionReport($request),
            'filters' => $filters,
            'categories' => Category::query()->orderBy('name')->get(),
            'locations' => Location::query()->orderBy('name')->get(),
        ]);
    }

    public function exportConditions(Request $request, string $format, ReportExporter $exporter): Response|RedirectResponse
    {
        $this->ensureFormat($format);

        return $exporter->download($format, $this->buildConditionReport($request));
    }

    private function buildItemsReport(Request $request): array
    {
        $items = $this->filteredItems($request)->get();

        $rows = $items->values()->map(function (InventoryItem $item, int $index) {
            return $this->itemRow($item, $index + 1, true);
        })->all();

        return [
            'title' => 'LAPORAN BARANG INVENTARIS',
            'subtitle' => 'BARANG HABIS PAKAI, ASET, SARANA DAN PRASARANA',
            'meta' => $this->reportMeta($request, ['type', 'category', 'location', 'condition', 'search']),
            'columns' => $this->itemColumns(true),
            'rows' => $rows,
            'orientation' => 'landscape',
            'sheet_name' => 'Laporan Barang',
            'filename' => 'laporan-barang-'.now()->format('Ymd-His'),
        ];
    }

    private function buildKirReport(Request $request, ?Location $location): array
    {
        $items = $location
            ? $this->filteredItems($request, true)->where('location_id', $location->id)->get()
            : collect();

        $rows = $items->values()->map(function (InventoryItem $item, int $index) {
            return $this->kirRow($item, $index + 1);
        })->all();

        $subtitle = $location
            ? 'RUANGAN '.$this->roomLabel($location->name)
            : 'PILIH RUANGAN TERLEBIH DAHULU';

        return [
            'title' => 'KARTU INVENTARIS RUANGAN',
            'subtitle' => $subtitle,
            'meta' => $this->reportMeta($request, ['location', 'type', 'search']),
            'columns' => $this->kirColumns(),
            'rows' => $rows,
            'orientation' => 'landscape',
            'sheet_name' => 'KIR',
            'filename' => 'kir-'.($location ? Str::slug($location->name) : 'ruangan').'-'.now()->format('Ymd-His'),
        ];
    }

    private function buildConditionReport(Request $request): array
    {
        $items = $this->filteredItems($request)->get();

        $rows = $items->values()->map(function (InventoryItem $item, int $index) {
            return $this->itemRow($item, $index + 1, true);
        })->all();

        $condition = $request->query('condition');
        $subtitle = in_array($condition, InventoryItem::CONDITIONS, true)
            ? 'KEADAAN: '.Str::upper($condition)
            : 'SEMUA KEADAAN BARANG';

        return [
            'title' => 'LAPORAN KEADAAN BARANG',
            'subtitle' => $subtitle,
            'meta' => $this->reportMeta($request, ['condition', 'type', 'category', 'location', 'search']),
            'columns' => $this->itemColumns(true),
            'rows' => $rows,
            'orientation' => 'landscape',
            'sheet_name' => 'Laporan Keadaan',
            'filename' => 'laporan-keadaan-'.now()->format('Ymd-His'),
        ];
    }

    private function filteredItems(Request $request, bool $onlyLocated = false): Builder
    {
        $type = $request->query('type');
        $categoryId = $request->query('category_id');
        $locationId = $request->query('location_id');
        $condition = $request->query('condition');
        $search = trim((string) $request->query('search', ''));

        return InventoryItem::query()
            ->with(['categoryRecord', 'locationRecord'])
            ->when($onlyLocated, fn ($query) => $query->whereIn('type', [InventoryItem::TYPE_ASSET, InventoryItem::TYPE_FACILITY]))
            ->when(in_array($type, InventoryItem::TYPES, true), function ($query) use ($type, $onlyLocated) {
                if ($onlyLocated && $type === InventoryItem::TYPE_CONSUMABLE) {
                    return;
                }

                $query->where('type', $type);
            })
            ->when(is_numeric($categoryId), fn ($query) => $query->where('category_id', (int) $categoryId))
            ->when(is_numeric($locationId), fn ($query) => $query->where('location_id', (int) $locationId))
            ->when(in_array($condition, InventoryItem::CONDITIONS, true), fn ($query) => $query->where('condition', $condition))
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
            ->orderBy('name');
    }

    private function itemColumns(bool $includeCalibration): array
    {
        $columns = [
            ['label' => 'No', 'width' => 5, 'align' => 'center'],
            ['label' => 'Kode Barang', 'width' => 13],
            ['label' => 'Nama Barang', 'width' => 27],
            ['label' => 'Jenis Barang', 'width' => 18],
            ['label' => 'Kategori', 'width' => 17],
            ['label' => 'Jumlah', 'width' => 7, 'align' => 'center'],
            ['label' => 'Satuan', 'width' => 7, 'align' => 'center'],
            ['label' => 'Lokasi / Ruangan', 'width' => 18],
            ['label' => 'Keadaan', 'width' => 14, 'align' => 'center'],
            ['label' => 'Tgl. Perolehan', 'width' => 12, 'align' => 'center'],
            ['label' => 'Harga', 'width' => 14, 'align' => 'right'],
        ];

        if ($includeCalibration) {
            $columns[] = ['label' => 'Kalibrasi', 'width' => 13, 'align' => 'center'];
            $columns[] = ['label' => 'Tgl. Kalibrasi', 'width' => 12, 'align' => 'center'];
        }

        $columns[] = ['label' => 'Tgl. Rusak / Hilang', 'width' => 13, 'align' => 'center'];
        $columns[] = ['label' => 'Tindak Lanjut', 'width' => 24];
        $columns[] = ['label' => 'Keterangan', 'width' => 24];

        return $columns;
    }

    private function kirColumns(): array
    {
        return [
            ['label' => 'No', 'width' => 5, 'align' => 'center'],
            ['label' => 'Kode Barang', 'width' => 14],
            ['label' => 'Nama Barang', 'width' => 32],
            ['label' => 'Kategori', 'width' => 20],
            ['label' => 'Jenis Barang', 'width' => 20],
            ['label' => 'Jumlah', 'width' => 8, 'align' => 'center'],
            ['label' => 'Satuan', 'width' => 8, 'align' => 'center'],
            ['label' => 'Keadaan', 'width' => 15, 'align' => 'center'],
            ['label' => 'Tgl. Perolehan', 'width' => 13, 'align' => 'center'],
            ['label' => 'Harga', 'width' => 16, 'align' => 'right'],
            ['label' => 'Kalibrasi', 'width' => 15, 'align' => 'center'],
            ['label' => 'Tgl. Kalibrasi', 'width' => 13, 'align' => 'center'],
            ['label' => 'Keterangan', 'width' => 28],
        ];
    }

    private function itemRow(InventoryItem $item, int $number, bool $includeCalibration): array
    {
        $row = [
            (string) $number,
            $item->code,
            $item->name,
            $item->type_label,
            $item->category,
            number_format($item->quantity, 0, ',', '.'),
            $item->unit,
            $item->hasLocation() ? ($item->location_name ?: '—') : '—',
            $item->condition ?: '—',
            $this->date($item->acquired_at),
            $this->price($item->price),
        ];

        if ($includeCalibration) {
            $row[] = $item->isAsset() ? $this->calibrationLabel($item) : '—';
            $row[] = $item->isAsset() ? $this->date($item->calibration_date) : '—';
        }

        $row[] = $item->needsConditionDetail() ? $this->date($item->condition_date) : '—';
        $row[] = $item->needsConditionDetail() ? ($item->follow_up ?: '—') : '—';
        $row[] = $item->notes ?: '—';

        return $row;
    }

    private function kirRow(InventoryItem $item, int $number): array
    {
        return [
            (string) $number,
            $item->code,
            $item->name,
            $item->category,
            $item->type_label,
            number_format($item->quantity, 0, ',', '.'),
            $item->unit,
            $item->condition ?: '—',
            $this->date($item->acquired_at),
            $this->price($item->price),
            $item->isAsset() ? $this->calibrationLabel($item) : '—',
            $item->isAsset() ? $this->date($item->calibration_date) : '—',
            $item->notes ?: '—',
        ];
    }

    private function reportMeta(Request $request, array $fields): array
    {
        $meta = ['Tanggal ekspor' => now()->format('d/m/Y H:i')];

        $type = $request->query('type');
        $categoryId = $request->query('category_id');
        $locationId = $request->query('location_id');
        $condition = $request->query('condition');
        $search = trim((string) $request->query('search', ''));

        if (in_array('type', $fields, true)) {
            $meta['Jenis barang'] = $this->typeLabel($type);
        }

        if (in_array('category', $fields, true)) {
            $category = is_numeric($categoryId) ? Category::find((int) $categoryId) : null;
            $meta['Kategori'] = $category?->name ?? 'Semua kategori';
        }

        if (in_array('location', $fields, true)) {
            $location = is_numeric($locationId) ? Location::find((int) $locationId) : null;
            $meta['Lokasi / ruangan'] = $location?->name ?? 'Semua lokasi';
        }

        if (in_array('condition', $fields, true)) {
            $meta['Keadaan'] = in_array($condition, InventoryItem::CONDITIONS, true) ? $condition : 'Semua keadaan';
        }

        if (in_array('search', $fields, true)) {
            $meta['Pencarian'] = $search !== '' ? $search : '—';
        }

        return $meta;
    }

    private function filters(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('search', '')),
            'type' => $request->query('type'),
            'category_id' => $request->query('category_id'),
            'location_id' => $request->query('location_id'),
            'condition' => $request->query('condition'),
        ];
    }

    private function selectedLocation(Request $request): ?Location
    {
        $locationId = $request->query('location_id');

        return is_numeric($locationId) ? Location::find((int) $locationId) : null;
    }

    private function typeLabel(?string $type): string
    {
        return match ($type) {
            InventoryItem::TYPE_CONSUMABLE => 'Barang habis pakai',
            InventoryItem::TYPE_ASSET => 'Barang aset',
            InventoryItem::TYPE_FACILITY => 'Sarana dan prasarana',
            default => 'Semua jenis',
        };
    }

    private function roomLabel(string $name): string
    {
        $label = Str::upper(trim($name));
        $label = preg_replace('/^RUANG(?:AN)?\s+/', '', $label) ?? $label;
        $label = trim($label);

        return $label !== '' ? $label : Str::upper(trim($name));
    }

    private function calibrationLabel(InventoryItem $item): string
    {
        return match ($item->calibration_status) {
            InventoryItem::CALIBRATION_DONE => 'Sudah dikalibrasi',
            InventoryItem::CALIBRATION_NOT_YET => 'Belum dikalibrasi',
            default => '—',
        };
    }

    private function date(mixed $value): string
    {
        if (! $value) {
            return '—';
        }

        return $value instanceof \Carbon\CarbonInterface
            ? $value->format('d/m/Y')
            : (string) $value;
    }

    private function price(?int $price): string
    {
        return $price === null ? '—' : 'Rp '.number_format($price, 0, ',', '.');
    }

    private function ensureFormat(string $format): void
    {
        abort_unless(in_array(strtolower($format), self::EXPORT_FORMATS, true), 404);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    public const TYPE_CONSUMABLE = 'consumable';

    public const TYPE_ASSET = 'asset';

    public const TYPE_FACILITY = 'facility';

    public const TYPES = [
        self::TYPE_CONSUMABLE,
        self::TYPE_ASSET,
        self::TYPE_FACILITY,
    ];

    public const CONDITION_GOOD = 'Baik';

    public const CONDITION_MINOR_DAMAGE = 'Rusak sedang';

    public const CONDITION_MAJOR_DAMAGE = 'Rusak berat';

    public const CONDITION_LOST = 'Hilang';

    public const CONDITION_NOT_FOUND = 'Tidak ditemukan';

    public const CONDITIONS = [
        self::CONDITION_GOOD,
        self::CONDITION_MINOR_DAMAGE,
        self::CONDITION_MAJOR_DAMAGE,
        self::CONDITION_LOST,
        self::CONDITION_NOT_FOUND,
    ];

    /**
     * Keadaan yang membutuhkan tanggal dan keterangan tindak lanjut.
     */
    public const CONDITION_DETAIL_REQUIRED = [
        self::CONDITION_MINOR_DAMAGE,
        self::CONDITION_MAJOR_DAMAGE,
        self::CONDITION_LOST,
        self::CONDITION_NOT_FOUND,
    ];

    public const CALIBRATION_DONE = 'sudah';

    public const CALIBRATION_NOT_YET = 'belum';

    public const CALIBRATION_STATUSES = [
        self::CALIBRATION_DONE,
        self::CALIBRATION_NOT_YET,
    ];

    protected $fillable = [
        'type',
        'code',
        'item_number',
        'name',
        'category',
        'category_id',
        'quantity',
        'price',
        'unit',
        'location',
        'location_id',
        'photo_path',
        'condition',
        'condition_date',
        'follow_up',
        'calibration_status',
        'calibration_date',
        'acquired_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'acquired_at' => 'date',
            'condition_date' => 'date',
            'calibration_date' => 'date',
        ];
    }

    public function categoryRecord(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function locationRecord(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * Nomor urut berikutnya untuk sebuah kategori, selalu 4 digit.
     *
     * Nomor diambil dari nilai terbesar yang sudah dipakai di kategori tersebut,
     * lalu ditambah satu. Baris lama yang belum punya item_number tetap dihitung
     * dengan membaca angka di akhir kolom code.
     */
    public static function nextNumberForCategory(int $categoryId, ?int $ignoreId = null): string
    {
        $max = static::query()
            ->where('category_id', $categoryId)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->get(['item_number', 'code'])
            ->reduce(function (int $carry, InventoryItem $item) {
                $number = $item->item_number;

                if (! ctype_digit((string) $number)) {
                    $number = preg_match('/(\d+)$/', (string) $item->code, $matches) === 1 ? $matches[1] : null;
                }

                return $number !== null && ctype_digit((string) $number)
                    ? max($carry, (int) $number)
                    : $carry;
            }, 0);

        return str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    public static function buildCode(string $categoryCode, string $itemNumber): string
    {
        return $categoryCode.'-'.$itemNumber;
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_ASSET => 'Barang aset',
            self::TYPE_FACILITY => 'Sarana dan prasarana',
            default => 'Barang habis pakai',
        };
    }

    public function getSuggestedItemNumberAttribute(): string
    {
        if ($this->item_number !== null) {
            return (string) $this->item_number;
        }

        return preg_match('/(\d+)$/', $this->code, $matches) === 1 ? $matches[1] : '';
    }

    public function isAsset(): bool
    {
        return $this->type === self::TYPE_ASSET;
    }

    public function isFacility(): bool
    {
        return $this->type === self::TYPE_FACILITY;
    }

    public function isConsumable(): bool
    {
        return $this->type === self::TYPE_CONSUMABLE;
    }

    public function hasLocation(): bool
    {
        return $this->isAsset() || $this->isFacility();
    }

    public function needsConditionDetail(): bool
    {
        return in_array($this->condition, self::CONDITION_DETAIL_REQUIRED, true);
    }

    public function isLost(): bool
    {
        return in_array($this->condition, [self::CONDITION_LOST, self::CONDITION_NOT_FOUND], true);
    }

    public function getConditionDateLabelAttribute(): string
    {
        return $this->isLost() ? 'Tanggal hilang' : 'Tanggal rusak';
    }

    public function getConditionDateLabelShortAttribute(): string
    {
        return $this->isLost() ? 'Tgl. hilang' : 'Tgl. rusak';
    }

    public function getLocationNameAttribute(): ?string
    {
        return $this->locationRecord?->name ?: $this->location;
    }

    public function getCalibrationStatusLabelAttribute(): string
    {
        return match ($this->calibration_status) {
            self::CALIBRATION_DONE => 'Sudah dikalibrasi',
            self::CALIBRATION_NOT_YET => 'Belum dikalibrasi',
            default => '—',
        };
    }
}

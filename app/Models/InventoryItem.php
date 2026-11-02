<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    public const TYPE_CONSUMABLE = 'consumable';

    public const TYPE_ASSET = 'asset';

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
        'photo_path',
        'condition',
        'acquired_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'acquired_at' => 'date',
        ];
    }

    public function categoryRecord(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
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
        return $this->type === self::TYPE_ASSET ? 'Barang aset' : 'Barang habis pakai';
    }

    public function getSuggestedItemNumberAttribute(): string
    {
        if ($this->item_number !== null) {
            return (string) $this->item_number;
        }

        return preg_match('/(\d+)$/', $this->code, $matches) === 1 ? $matches[1] : '';
    }
}

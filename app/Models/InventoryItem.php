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

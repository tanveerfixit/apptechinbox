<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ProtectorStock extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'protector_stocks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'brand',
        'model',
        'glass_type',
        'screen_size_inch',
        'dimensions_mm',
        'stock_qty',
        'min_threshold',
        'bin_location',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'stock_qty' => 'integer',
        'min_threshold' => 'integer',
    ];

    /**
     * Allowed glass type variants.
     */
    public const GLASS_TYPES = [
        'Loose Glasses',
        'Aokus Thin 3D Touch',
        'Aokus Cover Edge 9D',
        'Aokus Loose',
        'Aokus 9H',
        'Ven-Dens 9H',
    ];

    /**
     * Scope a query to only include low stock items (stock_qty <= min_threshold).
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock_qty', '<=', 'min_threshold');
    }

    /**
     * Helper method to check if the current stock item is low on stock.
     *
     * @return bool
     */
    public function isLowStock(): bool
    {
        return $this->stock_qty <= $this->min_threshold;
    }
}

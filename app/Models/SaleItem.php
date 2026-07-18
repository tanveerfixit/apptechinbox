<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model {
    protected $guarded = [];
    public function sale() {
        return $this->belongsTo(Sale::class);
    }
    public function variant() {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
    public function serialNumber() {
        return $this->belongsTo(SerialNumber::class);
    }
}

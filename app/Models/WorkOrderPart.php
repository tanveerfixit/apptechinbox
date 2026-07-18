<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrderPart extends Model {
    protected $guarded = [];
    public function variant() {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
    public function workOrder() {
        return $this->belongsTo(WorkOrder::class);
    }
}

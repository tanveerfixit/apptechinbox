<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model {
    protected $guarded = [];
    public function parts() {
        return $this->hasMany(WorkOrderPart::class);
    }
    public function sale() {
        return $this->hasOne(Sale::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhysicalInventoryItem extends Model
{
    protected $table = 'physical_inventory_items';

    protected $fillable = [
        'item_code', 'name', 'description', 'stock_total', 'stock_available',
        'location', 'active', 'created_by',
    ];

    protected $casts = [
        'stock_total' => 'integer',
        'stock_available' => 'integer',
        'active' => 'boolean',
    ];

    public function loans()
    {
        return $this->hasMany(PhysicalLoan::class, 'inventory_item_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

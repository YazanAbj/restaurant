<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'category',
        'image_path',
        'availability_status',
    ];
    

    /**
     * Get all the requirements (inventory items and quantities) for this menu item.
     */
    public function requirements()
    {
        return $this->hasMany(MenuItemRequirement::class);
    }

    /**
     * Get all inventory items related to this menu item via the pivot table.
     */
    public function inventoryItems()
    {
        return $this->belongsToMany(InventoryItem::class, 'menu_item_requirements')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $price
 * @property int $sort_order
 */
#[Fillable(['name', 'price', 'sort_order'])]
class ShippingMethod extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guideline extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'category',
        'user_role',
        'is_active'
    ];

    /**
     * A guideline has many items.
     */
    public function items()
    {
        return $this->hasMany(Item::class);
    }
}

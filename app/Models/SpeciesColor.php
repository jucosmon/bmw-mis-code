<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpeciesColor extends Model
{
    use HasFactory;

    protected $fillable = [
        'color_id',
        'species_id'
    ];

    public function color()
    {
        return $this->belongsTo(Color::class, 'color_id');
    }

    public function species()
    {
        return $this->belongsTo(Species::class, 'species_id');
    }

}

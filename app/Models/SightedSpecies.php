<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SightedSpecies extends Model
{
    use HasFactory;

    protected  $table = 'sighted_species';
    protected $fillable = [
        'latitude',
        'longitude',
        'sex',
        'size',
        'species_description',
        'behavior_observed',
        'species_id',
        'sighting_id',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function species()
    {
        return $this->belongsTo(Species::class, 'species_id');
    }

    public function sighting()
    {
        return $this->belongsTo(Sighting::class, 'sighting_id');
    }
}

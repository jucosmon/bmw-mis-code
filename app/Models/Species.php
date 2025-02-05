<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Species extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'name', 'scientific_name', 'common_name', 'local_name', 'category',
        'description', 'conservation_status', 'shape',
        'is_dangerous', 'is_active'
    ];

    /**
     * A species can have many media files.
     */
    public function mediaFiles()
    {
        return $this->hasMany(MediaFile::class);
    }

    public function strandedSpecies()
    {
        return $this->hasMany(StrandedSpecies::class);
    }

    public function sightedSpecies()
    {
        return $this->hasMany(SightedSpecies::class);
    }

    public function speciesColors()
    {
        return $this->hasMany(SpeciesColor::class);
    }

}

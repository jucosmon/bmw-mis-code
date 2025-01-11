<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StrandedSpecies extends Model
{
    use HasFactory;

    protected  $table = 'stranded_species';
    protected $fillable = [
        'condition_code',
        'latitude',
        'longitude',
        'sex',
        'length',
        'weight',
        'girth',
        'disposition',
        'disposal_site',
        'more_information',
        'is_released',
        'is_active',
        'species_id',
        'stranded_incident_id',
        'user_id'
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'length' => 'decimal:7',
        'weight' => 'decimal:7',
        'girth' => 'decimal:7',
        'is_released' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function species()
    {
        return $this->belongsTo(Species::class, 'species_id');
    }

    public function strandedIncident()
    {
        return $this->belongsTo(StrandedIncident::class, 'stranded_incident_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

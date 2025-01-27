<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sighting extends Model
{
    use HasFactory;

    protected $table = 'sightings';

    protected $fillable = [
        'certainty_level',
        'date',
        'time',
        'latitude',
        'longitude',
        'detailed_location',
        'more_information',
        'report_status',
        'is_active',
        'municipality_id',
        'barangay_id',
        'user_id',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
    ];

    // Define relationships

    /**
     * Get the user that owns the sighting.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the municipality associated with the sighting.
     */
    public function municipality()
    {
        return $this->belongsTo(Municipality::class, 'municipality_id');
    }

    /**
     * Get the barangay associated with the sighting.
     */
    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id');
    }

    public function mediaFiles()
    {
        return $this->hasMany(MediaFile::class);
    }


    public function sightedSpecies()
    {
        return $this->hasMany(SightedSpecies::class);
    }
    /**
     * Scope to filter incidents by report status.
     */
    public function scopeWithReportStatus($query, $status)
    {
        return $query->where('report_status', $status);
    }

    /**
     * Scope to filter active incidents.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

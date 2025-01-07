<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StrandedIncident extends Model
{
    use HasFactory;

    // Specify the table name if it doesn't follow Laravel's pluralization convention
    protected $table = 'stranded_incidents';

    // Define the fillable attributes for mass assignment
    protected $fillable = [
        'certainty_level',
        'date',
        'time',
        'species_involved',
        'quantity',
        'condition',
        'latitude',
        'longitude',
        'sea_state',
        'weather',
        'beach_type',
        'detailed_location',
        'more_information',
        'report_status',
        'is_false',
        'is_active',
        'municipality_id',
        'barangay_id',
        'user_id',
    ];

    // Specify the attributes that should be cast to specific types
    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_false' => 'boolean',
        'is_active' => 'boolean',
        'report_status' => 'string',
    ];

    // Define relationships

    /**
     * Get the user that owns the stranded incident.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the municipality associated with the stranded incident.
     */
    public function municipality()
    {
        return $this->belongsTo(Municipality::class, 'municipality_id');
    }

    /**
     * Get the barangay associated with the stranded incident.
     */
    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id');
    }

    /**
     * Get all the user actions related to the stranded incident.
     */
    public function respond_action()
    {
        return $this->hasMany(RespondAction::class);
    }
    public function report_action()
    {
        return $this->hasMany(ReportAction::class);
    }

    public function mediaFiles()
    {
        return $this->hasMany(MediaFile::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
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

    /**
     * Scope to check if the incident is false.
     */
    public function scopeFalseIncident($query)
    {
        return $query->where('is_false', true);
    }

}

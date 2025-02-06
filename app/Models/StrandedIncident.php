<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StrandedIncident extends Model
{
    use HasFactory;

    protected $table = 'stranded_incidents';

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
        'is_active',
        'municipality_id',
        'barangay_id',
        'user_id',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
        'report_status' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function municipality()
    {
        return $this->belongsTo(Municipality::class, 'municipality_id');
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id');
    }

    public function respondActions()
    {
        return $this->hasMany(RespondAction::class);
    }

    public function reportActions()
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

    public function strandedSpecies()
    {
        return $this->hasMany(StrandedSpecies::class);
    }

    public function scopeWithReportStatus($query, $status)
    {
        return $query->where('report_status', $status);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

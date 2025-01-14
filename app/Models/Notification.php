<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'category',
        'notif_for',
        'type',
        'is_read',
        'sighting_id',
        'stranded_incident_id',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the stranded incident associated with the comment.
     */
    public function strandedIncident()
    {
        return $this->belongsTo(StrandedIncident::class, 'stranded_incident_id');
    }

    public function sighting()
    {
        return $this->belongsTo(Sighting::class, 'sighting_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = ['text', 'stranded_incident_id', 'user_id', 'is_active'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the stranded incident associated with the comment.
     */
    public function stranded_incident()
    {
        return $this->belongsTo(StrandedIncident::class, 'stranded_incident_id');
    }
}

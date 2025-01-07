<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RespondAction extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'response_status',
        'user_id',
        'stranded_incident_id'
    ];

    public function stranded_incident()
    {
        return $this->belongsTo(StrandedIncident::class, 'stranded_incident_id');
    }
    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use App\Traits\HandlesFalseReports;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HandlesFalseReports;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'contact_number',
        'birthdate',
        'sex',
        'position',
        'false_report_count',
        'is_restricted',
        'restriction_start',
        'restriction_end',
        'is_active',
        'user_role',
        'municipality_id',
        'barangay_id',
    ];


    public function barangay(){
        return $this->belongsTo(Barangay::class, 'barangay_id');
    }

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

    public function strandedIncidents()
    {
        return $this->hasMany(StrandedIncident::class);
    }

    public function strandedSpecies()
    {
        return $this->hasMany(StrandedSpecies::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    public function respondActions()
    {
        return $this->hasMany(RespondAction::class);
    }
    public function reportActions()
    {
        return $this->hasMany(ReportAction::class);
    }

    public function sightings()
    {
        return $this->hasMany(Sighting::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_restricted' => 'boolean',
            'restriction_start' => 'datetime',
            'restriction_end' => 'datetime',
        ];
    }


}

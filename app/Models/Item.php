<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'count',
        'text',
        'guideline_id',
    ];

    /**
     * An item belongs to a guideline.
     */
    public function guideline()
    {
        return $this->belongsTo(Guideline::class, 'guideline_id');
    }

    public function mediaFiles()
    {
        return $this->hasMany(MediaFile::class);
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaFile extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'path',
        'name',
        'caption',
        'file_for',
        'type',
        'species_id',
        'comment_id',
        'sighting_id',
        'guideline_id',
        'stranded_incident_id'
    ];

    /**
     * A media file belongs to a species.
     */
    /**
     * A media file belongs to a species.
     */
    public function species()
    {
        return $this->belongsTo(Species::class);
    }

    /**
     * A media file belongs to a comment.
     */
    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

    /**
     * A media file belongs to a sighting.
     */
    public function sighting()
    {
        return $this->belongsTo(Sighting::class);
    }

    /**
     * A media file belongs to a guideline.
     */
    public function guideline()
    {
        return $this->belongsTo(Guideline::class);
    }

    /**
     * A media file belongs to a stranded incident.
     */
    public function strandedIncident()
    {
        return $this->belongsTo(StrandedIncident::class);
    }

    /**
     * Boot method to validate that only one foreign key is set.
     */
    protected static function booted()
    {
        static::saving(function ($mediaFile) {
            $relatedEntities = [
                $mediaFile->species_id,
                $mediaFile->comment_id,
                $mediaFile->sighting_id,
                $mediaFile->guideline_id,
                $mediaFile->stranded_incident_id,
            ];

            $nonNullCount = count(array_filter($relatedEntities));

            if ($nonNullCount > 1) {
                throw new \Exception('A MediaFile can only belong to one entity at a time.');
            }
        });
    }
}

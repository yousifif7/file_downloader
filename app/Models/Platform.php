<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Platform extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'extractor_class',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class);
    }
}

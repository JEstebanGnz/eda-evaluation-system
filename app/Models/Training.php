<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Training extends Model
{

    protected $guarded = [];

    public function commitments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Commitment::class);
    }

    use HasFactory;
}

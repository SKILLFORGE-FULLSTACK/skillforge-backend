<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'votable_type',
        'votable_id',
        'value',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

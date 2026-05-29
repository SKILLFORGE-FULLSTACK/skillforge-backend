<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RecruiterProfile extends Model
{
    use HasUuids;

    const CREATED_AT = null; // ← AJOUTER

    protected $fillable = [
        'user_id',
        'company_name',
        'company_size',
        'company_logo_url',
        'company_website',
        'industry',
        'positions_open',
        'credits_remaining',
        'total_contacts',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

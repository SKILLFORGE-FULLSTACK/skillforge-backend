<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruiterSavedProfile extends Model
{
    public $incrementing = false;
    public $timestamps   = false;

    protected $fillable = [
        'recruiter_id',
        'developer_id',
        'note',
        'saved_at',
    ];

    protected function casts(): array
    {
        return [
            'saved_at' => 'datetime',
        ];
    }

    public function developer()
    {
        return $this->belongsTo(User::class, 'developer_id');
    }

    public function recruiter()
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genealogy extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parent_id',
        'position',  // 'left' or 'right'
        'level',     // Represents the depth of the user in the genealogy tree, starting from 0 for the root user and incrementing by 1 for each subsequent level.
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }
}

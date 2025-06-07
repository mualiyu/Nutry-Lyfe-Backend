<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ranking extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'rank', 'pv_accumulated'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

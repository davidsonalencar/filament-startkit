<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Export extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'format',
        'type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

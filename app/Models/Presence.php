<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Presence extends Model
{
    use HasFactory;

    protected $table = 'presence';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'dates',
        'dept',
        'grade',
        'academic_year',
        'semester',
        'approved'
    ];

    public function presenceTrans()
    {
        return $this->hasMany(PresenceTrans::class, 'presence_id');
    }
}

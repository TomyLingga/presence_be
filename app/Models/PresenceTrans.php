<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresenceTrans extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $table = 'presence_trans';
    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'presence_id',
        'attend',
        'permission',
        'sick',
        'absent',
        'leaves',
        'checkin',
        'description'
    ];

    public function presence()
    {
        return $this->belongsTo(Presence::class, 'presence_id');
    }
}

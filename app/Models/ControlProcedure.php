<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ControlProcedure extends Model
{
    use HasFactory;
    public function control()
    {
        return $this->belongsTo(Control::class, 'control_id');
    }
}

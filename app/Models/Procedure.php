<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Procedure extends Model
{
    use HasFactory;
    public function controls()
    {
        return $this->belongsToMany(Control::class, 'control_procedures', 'procedure_id', 'control_id');
    }
}

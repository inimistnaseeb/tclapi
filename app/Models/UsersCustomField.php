<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersCustomField extends Model
{
    // The table associated with the model
    protected $table = 'users_custom_fields';

    // The attributes that are mass assignable
    protected $fillable = [
        'field_name',
        'field_type',
        'field_list_type',
        'status',
        'restricted',
        'created',
        'modified',
    ];

    // Enabling Laravel's timestamps
    public $timestamps = true;

    // Defining the custom names for the timestamps
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
}

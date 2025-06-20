<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserClient extends Model
{
    use SoftDeletes;

    // Specify the custom column name for soft deletes
    const DELETED_AT = 'deleted_date';

    protected $table = 'users_clients';

    protected $dates = ['deleted_date']; // Ensure this is handled as a date

    protected $fillable = [
        'user_id',
        'client_id',
        'created',
        'deleted',
        'deleted_date',
    ];

    /**
     * Override the method to specify the custom deleted_at column name.
     *
     * @return string
     */
    public function getDeletedAtColumn()
    {
        return 'deleted_date';
    }
}

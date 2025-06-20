<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Control extends Model
{
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
    protected $fillable = [
        'creator_id',
        'control_name',
        'control_type_id',
        'control_risk_id',
        'control_number',
        'score',
        'control_description',
        'preventive_detective',
        'automated',
        'control_effective_date',
        'never_due',
        'due_date',
        'first_due_date',
        'repeats',
        'frequency_id',
        'repeat_frequency',
        'repeat_month_by',
        'repeat_ends_on',
        'occurrences',
        'end_date',
        'user_id',
        'department_id',
        'backup_user0_id',
        'client_id',
        'set_reminder',
        'reminder_date',
        'rem_pre_month',
        'rem_pre_week',
        'rem_pre_day',
        'rem_today',
        'rem_post_daily',
        'rem_post_weekly',
    ];

    // hasOne relationship
    public function controlCompletion()
    {
        return $this->hasOne(ControlCompletion::class, 'control_id');
    }

    // hasMany relationships
    public function uploads()
    {
        return $this->hasMany(Upload::class, 'foreign_key')->where('model', 'Control');
    }

    public function controlOccurrences()
    {
        return $this->hasMany(ControlOccurrence::class, 'control_id')->where('deleted', '!=', true);
    }

    public function procedures()
    {
        return $this->belongsToMany(Procedure::class, 'control_procedures', 'control_id', 'procedure_id');
    }

    public function controlProcedures()
    {
        return $this->hasMany(ControlProcedure::class, 'control_id');
    }

    public function sharedContentsByMe()
    {
        return $this->hasMany(SharedContentByMe::class, 'content_id')->where('type', 'control');
    }

    // belongsTo relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function backupUser1()
    {
        return $this->belongsTo(User::class, 'backup_user0_id');
    }

    public function backupUser2()
    {
        return $this->belongsTo(User::class, 'backup_user1_id');
    }

    public function backupUser3()
    {
        return $this->belongsTo(User::class, 'backup_user2_id');
    }

    public function backupUser4()
    {
        return $this->belongsTo(User::class, 'backup_user3_id');
    }

    public function backupUser5()
    {
        return $this->belongsTo(User::class, 'backup_user4_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function controlType()
    {
        return $this->belongsTo(ControlType::class, 'control_type_id');
    }

    public function controlRisk()
    {
        return $this->belongsTo(ControlRisk::class, 'control_risk_id');
    }

    public function keyType()
    {
        return $this->belongsTo(KeyType::class, 'key_type_id');
    }

    public function reminder()
    {
        return $this->belongsTo(Reminder::class, 'reminder_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function frequency()
    {
        return $this->belongsTo(Frequency::class, 'frequency_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    function createVersion()
    {
        // Get the latest control number from the 'controls' table
        $latestControlNumber = DB::table('controls')
            ->orderBy('control_number_auto', 'desc')
            ->limit(1)
            ->value('control_number_auto');

        // Check if the latest control number exists and is not null
        if ($latestControlNumber) {
            // Increment the latest control number by 1
            return $latestControlNumber + 1;
        } else {
            // If no control number exists, start with '1000'
            return '1000';
        }
    }
}

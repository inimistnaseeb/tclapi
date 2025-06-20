<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', // Add 'id' to allow mass assignment
        'parent_id',
        'creator_id',
        'status_id',
        'task_name',
        'task_description',
        'priority_id',
        'department_id',
        'backup_user0_id',
        'client_id',
        'due_date',
        'first_due_date',
        'repeats',
        'frequency_id',
        'repeat_frequency',
        'repeat_month_by',
        //'repeat_starts_on',
        'repeat_ends_on',
        'occurrences',
        'end_date',
        'set_reminder',
        'reminder_date',
        'rem_pre_month',
        'rem_pre_week',
        'rem_pre_day',
        'rem_today',
        'rem_post_daily',
        'rem_post_weekly',
        // 'paste_image_data',
        //'tmp_id',
        'view_type',
        'email_on_complete'
    ];

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    public static function formatHasManyForSaveAll($data, $foreignKey)
    {
        $formatted_data = [];
        foreach ($data as $values) {
            $formatted_data[] = array($foreignKey => $values);
        }
        //debug($formatted_data);die;
        return $formatted_data;
    }

    public static function setstatusHasManyForSaveAll($data = array(), $foreignKey = array())
    {
        //$datavalue=$data[0];
        //$formatted_data[]=array_merge($datavalue,$foreignKey);
        $formatted_data = [];
        foreach ($data as $values) {
            $formatted_data[] = array_merge($values, $foreignKey);
        }
        //debug($formatted_data);die;
        return $formatted_data;
    }

    public static function __UnserializedBase64_Setting($setting_constant = '', $setting_var)
    {
        $setting_arr = unserialize($setting_constant);
        $setting = cipher(base64_decode($setting_arr[$setting_var]), Configure::read('Security.salt'));
        return unserialize($setting);
    }
}

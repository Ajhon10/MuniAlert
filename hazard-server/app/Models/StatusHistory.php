<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusHistory extends Model
{
    protected $table = 'report_status_history';

    protected $guarded = ['id'];
}

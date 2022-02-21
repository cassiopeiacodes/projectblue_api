<?php

namespace App\Models\Project;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTask extends Model
{
    use SoftDeletes;

    protected $table = 'project_task';

    protected $primaryKey = 'project_task_id';
    protected $guarded = 'project_id';

    protected $dates = ["end_at"];

    protected $fillable = [
        'task',
        'project_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

}

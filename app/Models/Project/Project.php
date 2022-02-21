<?php

namespace App\Models\Project;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Project extends Model
{
    use SoftDeletes;

    protected $table = 'project';

    protected $primaryKey = 'project_id';

    protected $fillable = [
        'project_name',
        'project_desc',
        'status',
    ];

//    public const PROJECT_STATUS = [
//        'idle',
//        'process',
//        'on hold',
//        'finish',
//        'cancel'
//    ];
}

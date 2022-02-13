<?php

namespace App\Models\Project;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectAll extends Model
{
    use SoftDeletes;

    protected $table = 'project_all';

    protected $primaryKey = 'project_id';

    protected $fillable = [
        'name',
        'desc',
    ];

    protected $casts = [
        'requirement' => 'array'
    ];

    public function activity() {
        return $this->hasMany('App\Models\Project\ProjectActivity','project_id','project_id')
            ->where('active_status',0)
            ->orderBy("project_activity_id","desc")
            ->limit(1);
    }
}

<?php

namespace App\Models\Project;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectActivity extends Model
{
    use SoftDeletes;

    protected $table = 'project_activity';

    protected $primaryKey = 'project_activity_id';

    protected $fillable = [
        'action',
        'status',
        'active_status',
        'project_id'
    ];

    protected $casts = [
        'evidence' => 'array'
    ];

    public function project() {
        return $this->belongsTo("App\Models\Project\ProjectAll",'project_id','project_id');
    }
}

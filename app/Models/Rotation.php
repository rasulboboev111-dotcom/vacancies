<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'old_branch_id',
        'new_branch_id',
        'old_position',
        'new_position',
        'old_structure',
        'new_structure',
        'rotation_date',
        'reason',
    ];

    protected $casts = [
        'rotation_date' => 'date',
    ];

    /**
     * Get the employee of this rotation.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the old branch of this rotation.
     */
    public function oldBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'old_branch_id');
    }

    /**
     * Get the new branch of this rotation.
     */
    public function newBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'new_branch_id');
    }
}

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
        'old_position_id',
        'new_position_id',
        'old_structure_id',
        'new_structure_id',
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

    /**
     * Get the old position of this rotation.
     */
    public function oldPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'old_position_id');
    }

    /**
     * Get the new position of this rotation.
     */
    public function newPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'new_position_id');
    }

    /**
     * Get the old structure of this rotation.
     */
    public function oldStructure(): BelongsTo
    {
        return $this->belongsTo(Structure::class, 'old_structure_id');
    }

    /**
     * Get the new structure of this rotation.
     */
    public function newStructure(): BelongsTo
    {
        return $this->belongsTo(Structure::class, 'new_structure_id');
    }
}

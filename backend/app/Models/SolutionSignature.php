<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $solution_id
 * @property string $pattern
 */
#[Fillable(['pattern'])]
class SolutionSignature extends Model
{
    /** @return BelongsTo<Solution, $this> */
    public function solution(): BelongsTo
    {
        return $this->belongsTo(Solution::class);
    }
}

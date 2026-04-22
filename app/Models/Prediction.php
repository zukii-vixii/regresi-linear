<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_id', 'user_id', 'x_input', 'y_predicted',
        'slope', 'intercept', 'r_squared',
    ];

    protected $casts = [
        'x_input'    => 'float',
        'y_predicted' => 'float',
        'slope'      => 'float',
        'intercept'  => 'float',
        'r_squared'  => 'float',
    ];

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataRow extends Model
{
    use HasFactory;

    protected $fillable = ['dataset_id', 'label', 'x_value', 'y_value'];

    protected $casts = [
        'x_value' => 'float',
        'y_value' => 'float',
    ];

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }
}

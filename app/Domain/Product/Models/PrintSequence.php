<?php

namespace App\Domain\Product\Models;

use Illuminate\Database\Eloquent\Model;

class PrintSequence extends Model
{
    protected $table = 'print_sequences';

    protected $fillable = [
        'date',
        'sequence_number',
    ];

    protected $casts = [
        'date' => 'date',
        'sequence_number' => 'integer',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParsedFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'original_name',
        'format',
        'file_size',
        'status',
        'metadata',
        'parsed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'parsed_at' => 'datetime',
        'file_size' => 'integer',
    ];

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}

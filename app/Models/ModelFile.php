<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelFile extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'original_filename',
        'stored_filename',
        'storage_path',
        'extension',
        'mime_type',
        'file_size',
        'status',
        'vertex_count',
        'triangle_count',
        'volume',
        'surface_area',
        'width',
        'height',
        'depth',
        'center_x',
        'center_y',
        'center_z',
        'min_x',
        'min_y',
        'min_z',
        'max_x',
        'max_y',
        'max_z',
        'metadata',
        'error_message',
        'parsed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'vertex_count' => 'integer',
            'triangle_count' => 'integer',
            'volume' => 'float',
            'surface_area' => 'float',
            'width' => 'float',
            'height' => 'float',
            'depth' => 'float',
            'center_x' => 'float',
            'center_y' => 'float',
            'center_z' => 'float',
            'min_x' => 'float',
            'min_y' => 'float',
            'min_z' => 'float',
            'max_x' => 'float',
            'max_y' => 'float',
            'max_z' => 'float',
            'metadata' => 'array',
            'parsed_at' => 'datetime',
        ];
    }
}

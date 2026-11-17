<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'actor_id',
        'action',
        'target_type',
        'target_id',
        'metadata',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public static function record(
        string $action,
        ?int $userId = null,
        ?int $actorId = null,
        ?string $targetType = null,
        ?int $targetId = null,
        array $metadata = [],
        ?string $ipAddress = null
    ): void {
        static::create([
            'user_id' => $userId,
            'actor_id' => $actorId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'metadata' => $metadata,
            'ip_address' => $ipAddress,
        ]);
    }
}

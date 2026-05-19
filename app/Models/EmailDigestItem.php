<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationEventType;
use Database\Factories\EmailDigestItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $user_id
 * @property NotificationEventType $event_type
 * @property array<string, mixed> $data
 * @property Carbon|null $sent_at
 */
#[Fillable(['user_id', 'event_type', 'data', 'sent_at'])]
class EmailDigestItem extends Model
{
    /** @use HasFactory<EmailDigestItemFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'event_type' => NotificationEventType::class,
            'data' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

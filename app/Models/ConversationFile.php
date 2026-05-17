<?php

namespace App\Models;

use App\Observers\ConversationFileObserver;
use Database\Factories\ConversationFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $ulid
 * @property int $conversation_id
 * @property ?int $comment_id
 * @property int $uploader_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property bool $is_inline_image
 */
#[ObservedBy([ConversationFileObserver::class])]
#[Fillable([
    'ulid',
    'conversation_id',
    'comment_id',
    'uploader_id',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size',
    'is_inline_image',
])]
class ConversationFile extends Model
{
    /** @use HasFactory<ConversationFileFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'is_inline_image' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $file): void {
            if (blank($file->ulid)) {
                $file->ulid = (string) Str::ulid();
            }
        });
    }

    /** @return BelongsTo<Conversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /** @return BelongsTo<Comment, $this> */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isAudio(): bool
    {
        return str_starts_with($this->mime_type, 'audio/');
    }

    /** @return Attribute<string, never> */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk($this->disk)->url($this->path));
    }
}

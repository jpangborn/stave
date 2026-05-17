<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ConversationFile>
 */
class ConversationFileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ulid = (string) Str::ulid();

        return [
            'ulid' => $ulid,
            'conversation_id' => Conversation::factory(),
            'comment_id' => null,
            'uploader_id' => User::factory(),
            'disk' => 'digital-ocean',
            'path' => "conversations/test/{$ulid}.pdf",
            'original_name' => $this->faker->word().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => $this->faker->numberBetween(1024, 5_000_000),
            'is_inline_image' => false,
        ];
    }

    public function inlineImage(): self
    {
        return $this->state(function () {
            $ulid = (string) Str::ulid();

            return [
                'ulid' => $ulid,
                'path' => "conversations/test/{$ulid}.jpg",
                'original_name' => $this->faker->word().'.jpg',
                'mime_type' => 'image/jpeg',
                'is_inline_image' => true,
            ];
        });
    }

    public function audio(): self
    {
        return $this->state(function () {
            $ulid = (string) Str::ulid();

            return [
                'ulid' => $ulid,
                'path' => "conversations/test/{$ulid}.mp3",
                'original_name' => $this->faker->word().'.mp3',
                'mime_type' => 'audio/mpeg',
            ];
        });
    }
}

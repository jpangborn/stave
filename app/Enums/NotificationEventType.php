<?php

namespace App\Enums;

enum NotificationEventType: string
{
    case COMMENT_MENTION = 'comment.mention';
    case CONVERSATION_REPLY = 'conversation.reply';
    case CONVERSATION_CREATED = 'conversation.created';
    case SERVICE_DISCUSSION_COMMENT = 'service.discussion.comment';

    public function label(): string
    {
        return match ($this) {
            self::COMMENT_MENTION => '@Mentions of me',
            self::CONVERSATION_REPLY => 'Replies in my conversations',
            self::CONVERSATION_CREATED => 'New conversations in my groups',
            self::SERVICE_DISCUSSION_COMMENT => 'Comments on services I\'m on',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::COMMENT_MENTION => 'Someone @mentions you. Always bypasses quiet hours.',
            self::CONVERSATION_REPLY => 'Someone replies in a conversation you\'re part of.',
            self::CONVERSATION_CREATED => 'A teammate starts a new conversation in a group you\'re in.',
            self::SERVICE_DISCUSSION_COMMENT => 'A teammate posts in the discussion for a service you\'re assigned to.',
        };
    }

    /** @return array<int, NotificationChannel> */
    public function defaultChannels(): array
    {
        return [
            NotificationChannel::MAIL,
            NotificationChannel::BROADCAST,
            NotificationChannel::WEBPUSH,
            NotificationChannel::DATABASE,
        ];
    }

    public function isMention(): bool
    {
        return $this === self::COMMENT_MENTION;
    }

    /** @return array<int, self> */
    public static function userConfigurable(): array
    {
        return self::cases();
    }
}

<?php

namespace App\Enums;

enum AccessRole: string
{
    case ADMIN = 'admin';
    case LITURGY_ADMIN = 'liturgy_admin';
    case LITURGY_USER = 'liturgy_user';
    case PASTORAL_CARE_ADMIN = 'pastoral_care_admin';
    case PASTORAL_CARE_USER = 'pastoral_care_user';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::LITURGY_ADMIN => 'Liturgy Admin',
            self::LITURGY_USER => 'Liturgy User',
            self::PASTORAL_CARE_ADMIN => 'Pastoral Care Admin',
            self::PASTORAL_CARE_USER => 'Pastoral Care User',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::LITURGY_ADMIN => 'Liturgy +',
            self::LITURGY_USER => 'Liturgy',
            self::PASTORAL_CARE_ADMIN => 'Care +',
            self::PASTORAL_CARE_USER => 'Care',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ADMIN => 'zinc',
            self::LITURGY_ADMIN, self::LITURGY_USER => 'emerald',
            self::PASTORAL_CARE_ADMIN, self::PASTORAL_CARE_USER => 'rose',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ADMIN => 'shield-check',
            self::LITURGY_USER => 'musical-note',
            self::LITURGY_ADMIN => 'lock-closed',
            self::PASTORAL_CARE_ADMIN, self::PASTORAL_CARE_USER => 'heart',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ADMIN => 'Full access. Includes everything in Liturgy and Pastoral Care.',
            self::LITURGY_ADMIN => 'Can manage the song & reading library, templates, and assign other Liturgy users.',
            self::LITURGY_USER => 'Can plan and edit services, view the song & reading library.',
            self::PASTORAL_CARE_ADMIN => 'Can manage all pastoral assignments, view all notes, and reassign care relationships.',
            self::PASTORAL_CARE_USER => 'Can see assigned congregants, message them, and write private pastoral notes.',
        };
    }

    public function iconBgClass(): string
    {
        return match ($this) {
            self::LITURGY_USER, self::LITURGY_ADMIN => 'bg-emerald-100',
            self::PASTORAL_CARE_USER, self::PASTORAL_CARE_ADMIN => 'bg-rose-100',
            self::ADMIN => 'bg-zinc-900',
        };
    }

    public function iconColorClass(): string
    {
        return match ($this) {
            self::LITURGY_USER, self::LITURGY_ADMIN => 'text-emerald-700',
            self::PASTORAL_CARE_USER, self::PASTORAL_CARE_ADMIN => 'text-rose-700',
            self::ADMIN => 'text-white',
        };
    }

    /** @return array<string, list<self>> */
    public static function groupedForDisplay(): array
    {
        return [
            'Liturgy' => [self::LITURGY_USER, self::LITURGY_ADMIN],
            'Pastoral Care' => [self::PASTORAL_CARE_USER, self::PASTORAL_CARE_ADMIN],
            'Workspace' => [self::ADMIN],
        ];
    }
}

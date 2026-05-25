<?php

namespace App\Enums;

enum MembershipStatus: string
{
    case VISITOR = 'visitor';
    case ADHERENT = 'adherent';
    case CATECHUMEN = 'catechumen';
    case MEMBER = 'member';
    case TERMINATED = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::VISITOR => 'Visitor',
            self::ADHERENT => 'Adherent',
            self::CATECHUMEN => 'Catechumen',
            self::MEMBER => 'Member',
            self::TERMINATED => 'Terminated',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::VISITOR => 'zinc',
            self::ADHERENT => 'sky',
            self::CATECHUMEN => 'amber',
            self::MEMBER => 'emerald',
            self::TERMINATED => 'zinc',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::VISITOR => 'face-smile',
            self::ADHERENT => 'users',
            self::CATECHUMEN => 'book-open',
            self::MEMBER => 'home-modern',
            self::TERMINATED => 'archive-box',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::VISITOR => 'First-time or occasional attender.',
            self::ADHERENT => 'Regular attender, not yet pursuing membership.',
            self::CATECHUMEN => 'In catechesis, pursuing communing membership.',
            self::MEMBER => 'Communing member of the congregation.',
            self::TERMINATED => 'No longer a member or attender.',
        };
    }
}

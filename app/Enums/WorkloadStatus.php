<?php

namespace App\Enums;

enum WorkloadStatus: string
{
    case FREE = 'Free';
    case ALMOST_DONE = 'Almost Done';
    case ONGOING = 'Ongoing';
    case OVERLOADED = 'Overloaded';

    public function label(): string
    {
        return $this->value;
    }

    public function severity(): Severity
    {
        return match ($this) {
            self::FREE => Severity::SUCCESS,
            self::ALMOST_DONE => Severity::INFO,
            self::ONGOING => Severity::WARNING,
            self::OVERLOADED => Severity::DANGER,
        };
    }

    public static function fromId(int $id): ?self
    {
        return match ($id) {
            1 => self::FREE,
            2 => self::ALMOST_DONE,
            3 => self::ONGOING,
            4 => self::OVERLOADED,
            default => null,
        };
    }

    public function id(): int
    {
        return match ($this) {
            self::FREE => 1,
            self::ALMOST_DONE => 2,
            self::ONGOING => 3,
            self::OVERLOADED => 4,
        };
    }
}

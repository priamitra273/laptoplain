<?php

namespace App\Data\Project\Lazy;

use App\Models\MsSprintStatus;
use Spatie\LaravelData\Data;

/**
 * Slim sprint-status shape (id, name, severity) for the backlog board header tag.
 */
class SprintStatusData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $severity,
    ) {}

    public static function fromModel(MsSprintStatus $status): self
    {
        return new self(
            id: (int) $status->id,
            name: (string) $status->name,
            severity: $status->severity !== null ? (string) $status->severity : null,
        );
    }
}

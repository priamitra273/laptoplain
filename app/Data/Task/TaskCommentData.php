<?php

namespace App\Data\Task;

use App\Data\Comment\CommentReactionData;
use App\Data\UserData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class TaskCommentData extends Data
{
    public function __construct(
        public int $id,
        public string $body,

        /** @var array<int, CommentReactionData> */
        #[MapInputName('reaction_group_count')]
        public ?array $reactions,

        public ?string $current_user_reaction,

        public UserData $user,

        /** @var array<int, TaskCommentData> */
        public array $replies,

        public Carbon $created_at,
        public Carbon $updated_at,
    ) {}
}

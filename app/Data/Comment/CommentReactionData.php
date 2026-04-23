<?php

namespace App\Data\Comment;

use Spatie\LaravelData\Data;

class CommentReactionData extends Data
{
    public function __construct(
        public string $reaction,
        public int $count,
    ) {}
}

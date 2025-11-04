<?php

namespace App\Services;

use InvalidArgumentException;
use Sqids\Sqids;

class SqidsService
{
    protected Sqids $sqids;

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->sqids = new Sqids(
            alphabet: config('services.sqids.alphabet'),
            minLength: config('services.sqids.min_length')
        );
    }

    public function encode(int $value): string
    {
        return $this->sqids->encode([$value]);
    }

    public function decode(string $hashed): int
    {
        if (strlen($hashed) != config('services.sqids.min_length')) {
            throw new InvalidArgumentException('Invalid Hashed ID');
        }

        $result = $this->sqids->decode($hashed);

        if (count($result) != 1) {
            throw new InvalidArgumentException('Invalid Hashed ID');
        }

        return $result[0];
    }
}

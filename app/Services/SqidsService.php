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

    public function rec_encode_ids_in_list($data)
    {
        if ($data instanceof \Illuminate\Support\Collection) {
            return $data->map(fn($item) => $this->rec_encode_ids_in_list($item));
        }

        if ($data instanceof \Illuminate\Database\Eloquent\Model) {
            $data = $data->toArray();
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if ((preg_match('/_id$/', $key) || $key === 'id') && is_numeric($value)) {
                    try {
                        $data[$key] = $this->encode((int)$value);
                    } catch (\Throwable $e) {
                    }
                } elseif (is_array($value)) {
                    $data[$key] = $this->rec_encode_ids_in_list($value);
                }
            }
        }

        return $data;
    }

    public function rec_decode_ids_in_list($data)
    {
        if ($data instanceof \Illuminate\Support\Collection) {
            return $data->map(fn($item) => $this->rec_decode_ids_in_list($item));
        }

        if ($data instanceof \Illuminate\Database\Eloquent\Model) {
            $data = $data->toArray();
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if ((preg_match('/_id$/', $key) || $key === 'id') && is_string($value)) {
                    try {
                        $data[$key] = $this->decode($value);
                    } catch (\InvalidArgumentException $e) {
                    }
                } elseif (is_array($value)) {
                    $data[$key] = $this->rec_decode_ids_in_list($value);
                }
            }
        }

        return $data;
    }
}

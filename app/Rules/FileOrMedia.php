<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class FileOrMedia implements ValidationRule
{
    public function __construct(
        protected ?string $extensions = null,
        protected ?string $mimeTypes = null,
        protected ?int $maxSize = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value instanceof UploadedFile) {
            $this->validateFile($attribute, $value, $fail);
        } elseif (is_array($value)) {
            $this->validateMediaArray($attribute, $value, $fail);
        } else {
            $fail("The :attribute must be a file or a media reference.");
        }
    }

    protected function validateFile(string $_attribute, UploadedFile $file, Closure $fail): void
    {
        if ($this->extensions !== null) {
            $allowed = array_map(
                fn($ext) => ltrim(strtolower(trim($ext)), '.'),
                explode(',', $this->extensions)
            );
            $ext = strtolower($file->getClientOriginalExtension());

            if (! in_array($ext, $allowed)) {
                $fail("The :attribute must be a file of type: {$this->extensions}.");
                return;
            }
        }

        if ($this->mimeTypes !== null) {
            $patterns = array_map(fn($m) => trim($m), explode(',', $this->mimeTypes));
            $fileMime = strtolower($file->getMimeType() ?? '');

            if (! $this->matchesMimeType($fileMime, $patterns)) {
                $fail("The :attribute must be a file of mime type: {$this->mimeTypes}.");
                return;
            }
        }

        if ($this->maxSize !== null && $file->getSize() > $this->maxSize * 1024) {
            $fail("The :attribute may not be greater than {$this->maxSize} kilobytes.");
        }
    }

    protected function validateMediaArray(string $_attribute, array $value, Closure $fail): void
    {
        $uuid = $value['uuid'] ?? null;

        if (empty($uuid)) {
            $fail("The :attribute must contain a valid uuid.");
            return;
        }

        $exists = DB::table('media')->where('uuid', $uuid)->exists();

        if (! $exists) {
            $fail("The :attribute references a media that does not exist.");
        }
    }

    protected function matchesMimeType(string $fileMime, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            $pattern = strtolower($pattern);

            if (str_ends_with($pattern, '/*')) {
                $prefix = rtrim($pattern, '*');
                if (str_starts_with($fileMime, $prefix)) {
                    return true;
                }
            } elseif ($fileMime === $pattern) {
                return true;
            }
        }

        return false;
    }
}

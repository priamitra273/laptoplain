<?php

namespace App\Services\DynamicQuery;

use RuntimeException;

/**
 * Thrown when a dynamic query request violates the registry allowlist or
 * structural rules. The message is surfaced verbatim to the AI client.
 */
class QueryException extends RuntimeException {}

<?php

namespace App\Services\DynamicQuery;

use Illuminate\Contracts\Config\Repository as Config;

/**
 * Typed accessor over config/mcp_query.php — the single source of truth for
 * which models, columns, relations, joins and operators are queryable.
 */
class QueryRegistry
{
    public function __construct(protected Config $config) {}

    public function has(string $alias): bool
    {
        return $this->config->has("mcp_query.models.{$alias}");
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $alias): array
    {
        if (! $this->has($alias)) {
            throw new QueryException("Unknown model '{$alias}'. Call the list-queryable-models tool to see available models.");
        }

        return $this->config->get("mcp_query.models.{$alias}");
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function models(): array
    {
        return $this->config->get('mcp_query.models', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return $this->config->get('mcp_query.defaults', []);
    }
}

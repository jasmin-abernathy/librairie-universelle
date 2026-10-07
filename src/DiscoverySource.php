<?php

declare(strict_types=1);

interface DiscoverySource
{
    public function key(): string;
    public function name(): string;
    public function search(string $query, int $limit = 8): array;
}

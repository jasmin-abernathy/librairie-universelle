<?php

declare(strict_types=1);

interface CatalogSource
{
    public function key(): string;

    /**
     * @return array<int, array{
     *     external_id:string,
     *     title:string,
     *     publisher:?string,
     *     publication_date:?string,
     *     language:?string,
     *     medium:string,
     *     file_format:?string,
     *     isbn13:?string,
     *     source_url:string,
     *     cover_url:?string,
     *     contributors:array<int, array{name:string, role:string}>
     * }>
     */
    public function searchEditions(string $title, string $author, int $limit = 20): array;
}

<?php

declare(strict_types=1);

interface OfferSource
{
    public function name(): string;

    /**
     * @return array<int, array{
     *   external_id:string,
     *   offer_type:string,
     *   price_cents:?int,
     *   currency:?string,
     *   availability:?string,
     *   url:string,
     *   file_format:?string,
     *   drm_type:?string
     * }>
     */
    public function offersForIsbn(string $isbn13): array;
}

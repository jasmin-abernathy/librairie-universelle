<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/ApprovedPrintPriceSnapshot.php';
$valid = ApprovedPrintPriceSnapshot::grids('2026-10-17');
if (count($valid) !== 1 || ($valid[0]['amounts_cents']['200']['30'] ?? null) !== 29151) {
    throw new RuntimeException('Tarif de référence Corep absent ou invalide.');
}
if (ApprovedPrintPriceSnapshot::grids('2026-11-18') !== []) {
    throw new RuntimeException('Tarifs conservés après la date de réexamen.');
}
if (isset($valid[0]['amounts_cents']['180'])) {
    throw new RuntimeException('Interpolation interdite.');
}
echo "Snapshot tarifs autorisés : OK\n";

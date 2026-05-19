<?php

declare(strict_types=1);
?>

<div class="card-shell stack-card">
    <div class="section-title">
        <div>
            <h2>Booking Status</h2>
            <p>Overall trip distribution.</p>
        </div>
    </div>
    <?php $metricCounts = $bookingStatusCounts ?? []; ?>
    <?php require __DIR__ . '/metric-rows.php'; ?>
</div>

<div class="card-shell stack-card">
    <div class="section-title">
        <div>
            <h2>Resource Readiness</h2>
            <p>Cars and drivers at a glance.</p>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-12">
            <div class="soft-note mb-2">Cars</div>
            <?php $metricCounts = $carStatusCounts ?? []; ?>
            <?php require __DIR__ . '/metric-rows.php'; ?>
        </div>
        <div class="col-12">
            <div class="soft-note mb-2">Drivers</div>
            <?php $metricCounts = $driverStatusCounts ?? []; ?>
            <?php require __DIR__ . '/metric-rows.php'; ?>
        </div>
    </div>
</div>

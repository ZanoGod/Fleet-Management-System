<?php

declare(strict_types=1);

$summary = ($summary ?? []) + [
    'total_bookings' => 0,
    'active_bookings' => 0,
    'total_cars' => 0,
    'available_cars' => 0,
    'total_drivers' => 0,
    'available_drivers' => 0,
];
?>

<section class="overview-grid">
    <div class="card-shell overview-card">
        <span>Total Bookings</span>
        <strong><?= e((string) $summary['total_bookings']) ?></strong>
        <small>All trip records in the system</small>
    </div>
    
    <div class="card-shell overview-card">
        <span>Available Cars</span>
        <strong><?= e((string) $summary['available_cars']) ?></strong>
        <small><?= e((string) $summary['total_cars']) ?> Total Cars</small>
    </div>
    
    <div class="card-shell overview-card">
        <span>Available Drivers</span>
        <strong><?= e((string) $summary['available_drivers']) ?></strong>
        <small><?= e((string) $summary['total_drivers']) ?> Total Drivers</small>
    </div>

    <div class="card-shell overview-card">
        <span>Ongoing </span>
        <strong><?= e((string) $summary['active_bookings']) ?></strong>
        <small>Confirmed bookings not yet ended</small>
    </div>
</section>

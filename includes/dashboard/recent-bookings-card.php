<?php

declare(strict_types=1);
$recentBookings = $recentBookings ?? [];
?>

<div class="card-shell section-card">
    <div class="section-title">
        <div>
            <h2>Recent Bookings</h2>
            <p>Latest assignments across vehicles and drivers.</p>
        </div>
        <a class="btn btn-shell" href="bookings.php">View All</a>
    </div>

    <div class="table-responsive">
        <table class="table data-table dashboard-table">
            <thead>
                <tr>
                    <th>Guest / Company</th>
                    <th>Car</th>
                    <th>Driver</th>
                    <th>Dates</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recentBookings === []): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted-soft">No bookings available yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentBookings as $booking): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e($booking['guest_company_name']) ?></div>
                                <div class="soft-note"><?= e(booking_operator_display($booking)) ?></div>
                            </td>
                            
                            <td>
                                <div class="d-flex flex-column align-items-start">
                                    <?php foreach (booking_car_entries($booking) as $carLabel): ?>
                                        <span class="table-pill car-pill mb-1"><?= e($carLabel) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>

                            <td><span class="table-pill driver-pill"><?= e(booking_driver_display($booking)) ?></span></td>
                            <td>
                                <?= e(format_display_date($booking['start_date'])) ?><br>
                                <span class="soft-note"><?= e(format_display_date($booking['end_date'])) ?></span>
                            </td>
                            <td><span class="status-pill <?= e(status_badge_class($booking['status'])) ?>"><?= e($booking['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

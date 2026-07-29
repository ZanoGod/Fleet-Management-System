<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/monthly-contracts-data.php';

$activePage = 'monthly-contract-dashboard';
$navigationType = 'monthly_contract';
$navigationItems = monthly_contract_navigation();
$sidebarKicker = 'Fleet Management';
$sidebarTitle = 'Monthly Contracts';
$sidebarBrandMark = 'GSS';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid contract.');
    redirect('monthly-contracts.php');
}

if (!$db instanceof mysqli) {
    set_flash('danger', 'Database connection failed.');
    redirect('monthly-contracts.php');
}

$contract = monthly_contract_find($db, $id);

if (!$contract) {
    set_flash('danger', 'Contract not found.');
    redirect('monthly-contracts.php');
}

$pageKicker = 'Monthly Contract';
$pageTitle = 'Contract Details';
$pageSummary = 'View complete monthly contract information.';

$pageActions = '
<a href="monthly-contracts.php" class="btn btn-shell">
    <i class="fa fa-arrow-left"></i> Back
</a>

<a href="contract_edit.php?id=' . (int)$contract['id'] . '" class="btn btn-dashboard"> Edit
</a>

';

$pageStyles = [
    'assets/css/monthly-contracts.css',
];

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/messages.php';
?>

<div class="container-fluid">

    <!-- Summary -->

    <div class="card-shell section-card mb-4">
        <div class="row align-items-center g-4">

            <div class="col-lg-8">

                <h2 class="mb-2">

                    <?= e($contract['contract_no']) ?>

                </h2>

                <div class="mb-1">

                    <strong>Customer:</strong>

                    <?= e($contract['customer_name']) ?>

                </div>

                <div class="mb-1">

                    <strong>Vehicle:</strong>

                    <?= e($contract['car_type']) ?>

                    (<?= e($contract['plate_no']) ?>)

                </div>

                <div>

                    <strong>Driver:</strong>

                    <?= e($contract['driver_name'] ?: '-') ?>

                </div>

            </div>

            <div class="col-lg-4 text-lg-end">

                <div class="mb-2">

                    <span class="status-pill <?= e(monthly_contract_status_badge_class($contract['status'])) ?>">

                        <?= e($contract['status']) ?>

                    </span>

                </div>

                <div class="mb-2">

                    <span class="resource-pill <?= e(monthly_contract_rental_badge_class($contract['rental_status'])) ?>">

                        <?= e($contract['rental_status']) ?>

                    </span>

                </div>

                <small class="text-muted">

                    <?= e(format_display_date($contract['start_date'])) ?>

                    →

                    <?= e(format_display_date($contract['end_date'])) ?>

                </small>

            </div>

        </div>

    </div>



    <div class="row g-4">

        <!-- Contract Information -->

        <div class="col-lg-6">

            <div class="card-shell section-card h-100">

                <div class="section-title">

                    <div>

                        <h2>Contract Information</h2>

                        <p>General contract information.</p>

                    </div>

                </div>

                <div class="row g-3">

                    <div class="col-md-6">

                        <strong>Contract No.</strong>

                        <div><?= e($contract['contract_no']) ?></div>

                    </div>

                    <div class="col-md-6">

                        <strong>Assignment Type</strong>

                        <div><?= e($contract['assignment_type']) ?></div>

                    </div>

                    <div class="col-md-6">

                        <strong>Start Date</strong>

                        <div><?= e(format_display_date($contract['start_date'])) ?></div>

                    </div>

                    <div class="col-md-6">

                        <strong>End Date</strong>

                        <div><?= e(format_display_date($contract['end_date'])) ?></div>

                    </div>

                    <div class="col-md-6">

                        <strong>Status</strong>

                        <div>

                            <span class="status-pill <?= e(monthly_contract_status_badge_class($contract['status'])) ?>">
                                <?= e($contract['status']) ?>
                            </span>

                        </div>

                    </div>

                    <div class="col-md-6">

                        <strong>Payment</strong>

                        <div>

                            <span class="resource-pill <?= e(monthly_contract_rental_badge_class($contract['rental_status'])) ?>">
                                <?= e($contract['rental_status']) ?>
                            </span>

                        </div>

                    </div>

                    <div class="col-md-12">

                        <strong>Duration</strong>

                        <div>

                            <?php

                            $start = new DateTime($contract['start_date']);
                            $end   = new DateTime($contract['end_date']);

                            echo e((string)$start->diff($end)->days);

                            ?>

                            Days

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- Customer Information -->

        <div class="col-lg-6">

            <div class="card-shell section-card h-100">

                <div class="section-title">

                    <div>

                        <h2>Customer Information</h2>

                        <p>Customer details.</p>

                    </div>

                </div>

                <div class="row g-3">

                    <div class="col-md-6">

                        <strong>Company</strong>

                        <div><?= e($contract['customer_name']) ?></div>

                    </div>

                    <div class="col-md-6">

                        <strong>Guest</strong>

                        <div><?= e($contract['guest_name']) ?></div>

                    </div>

                    <div class="col-md-6">

                        <strong>Contact Person</strong>

                        <div><?= e($contract['contact_person']) ?></div>

                    </div>

                    <div class="col-md-6">

                        <strong>Phone</strong>

                        <div><?= e($contract['customer_phone']) ?></div>

                    </div>

                    <div class="col-12">

                        <strong>Email</strong>

                        <div><?= e($contract['customer_email']) ?></div>

                    </div>

                    <div class="col-12">

                        <strong>Address</strong>

                        <div>

                            <?= nl2br(e($contract['customer_address'])) ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="row g-4 mt-1">

        <!-- Vehicle Information -->

        <div class="col-lg-6">

            <div class="card-shell section-card h-100">

                <div class="section-title">
                    <div>
                        <h2>Vehicle Information</h2>
                        <p>Assigned vehicle details.</p>
                    </div>
                </div>

                <div class="row g-3">

                    <div class="col-md-6">
                        <strong>Car Type</strong>
                        <div><?= e($contract['car_type'] ?: '-') ?></div>
                    </div>

                    <div class="col-md-6">
                        <strong>Car Number</strong>
                        <div><?= e($contract['plate_no'] ?: '-') ?></div>
                    </div>

                    <div class="col-md-6">
                        <strong>Vehicle Model</strong>
                        <div><?= e($contract['model_name'] ?: '-') ?></div>
                    </div>

                    <div class="col-md-6">
                        <strong>Even / Odd</strong>
                        <div><?= e($contract['even_odd'] ?: '-') ?></div>
                    </div>

                </div>

            </div>

        </div>



        <!-- Driver Information -->

        <div class="col-lg-6">

            <div class="card-shell section-card h-100">

                <div class="section-title">
                    <div>
                        <h2>Driver Information</h2>
                        <p>Assigned driver details.</p>
                    </div>
                </div>

                <div class="row g-3">

                    <div class="col-md-6">
                        <strong>Driver Name</strong>
                        <div><?= e($contract['driver_name'] ?: '-') ?></div>
                    </div>

                    <div class="col-md-6">
                        <strong>Driver Phone</strong>
                        <div><?= e($contract['driver_phone'] ?: '-') ?></div>
                    </div>

                    <div class="col-md-12">
                        <strong>Operator</strong>
                        <div><?= e(monthly_contract_operator_display($contract)) ?></div>
                    </div>

                </div>

            </div>

        </div>

    </div>



    <div class="row g-4 mt-1">



        <!-- Insurance Information -->

        <div class="col-lg-6">

            <div class="card-shell section-card h-100">

                <div class="section-title">
                    <div>
                        <h2>Insurance Information</h2>
                        <p>Insurance details.</p>
                    </div>
                </div>

                <div class="row g-3">

                    <div class="col-md-8">

                        <strong>Insurance Company</strong>

                        <div>

                            <?= e($contract['insurance_company'] ?: '-') ?>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <strong>Insurance Price</strong>

                        <div>

                            <?= e(number_format((float)$contract['insurance_price'], )) ?> MMK
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- Financial Information -->

        <div class="col-lg-6">

            <div class="card-shell section-card h-100">

                <div class="section-title">
                    <div>
                        <h2>Financial Information</h2>
                        <p>Rental and payment.</p>
                    </div>
                </div>

                <div class="row g-3">

                    <div class="col-md-6">

                        <strong>Rental Price</strong>

                        <div>

                            <?= e(monthly_contract_money($contract['monthly_rate'])) ?>

                            <?= e($contract['rental_currency']) ?>

                        </div>

                    </div>

                    <div class="col-md-6">

                        <strong>Rental Status</strong>

                        <div>

                            <span class="resource-pill <?= e(monthly_contract_rental_badge_class($contract['rental_status'])) ?>">

                                <?= e($contract['rental_status']) ?>
                            </span>

                        </div>

                    </div>

                    <div class="col-md-6">

                        <strong>Contract Status</strong>

                        <div>

                            <span class="status-pill <?= e(monthly_contract_status_badge_class($contract['status'])) ?>">

                                <?= e($contract['status']) ?>

                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <div class="row g-4 mt-1">

        <!-- Service Information -->

        <div class="col-lg-6">

            <div class="card-shell section-card h-100">

                <div class="section-title">
                    <div>
                        <h2>Service Information</h2>
                        <p>Operation schedule.</p>
                    </div>
                </div>

                <div class="row g-3">

                    <div class="col-md-6">
                        <strong>Off Day</strong>
                        <div><?= e($contract['off_day'] ?: '-') ?></div>
                    </div>

                    <div class="col-md-6">
                        <strong>Service Hour</strong>
                        <div><?= e($contract['service_hour'] ?: '-') ?></div>
                    </div>

                    <div class="col-12">

                        <strong>Operation Sheet</strong>

                        <div class="border rounded p-3 bg-light mt-2">

                            <?php if (!empty($contract['operation_sheet'])): ?>

                                <?= nl2br(e($contract['operation_sheet'])) ?>

                            <?php else: ?>

                                <span class="text-muted">
                                    No operation sheet.
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- Internal Information -->

        <div class="col-lg-6">

            <div class="card-shell section-card h-100">

                <div class="section-title">
                    <div>
                        <h2>Internal Information</h2>
                        <p>Office reference.</p>
                    </div>
                </div>

                <div class="row g-3">

                    <div class="col-md-6">

                        <strong>Prepared By</strong>

                        <div>

                            <?= e($contract['prepared_by'] ?: '-') ?>

                        </div>

                    </div>

                    <div class="col-md-6">

                        <strong>Invoice Sender</strong>

                        <div>

                            <?= e($contract['invoice_sender'] ?: '-') ?>

                        </div>

                    </div>

                    <div class="col-md-6">

                        <strong>Checked By</strong>

                        <div>

                            <?= e($contract['checked_by'] ?: '-') ?>

                        </div>

                    </div>

                    <div class="col-md-6">

                        <strong>Created At</strong>

                        <div>

                            <?= e(date('d M Y H:i', strtotime($contract['created_at']))) ?>

                        </div>

                    </div>

                    <div class="col-md-6">

                        <strong>Last Updated</strong>

                        <div>

                            <?= e(date('d M Y H:i', strtotime($contract['updated_at']))) ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <div class="row g-4 mt-1">

        <!-- Terms & Conditions -->

        <div class="col-lg-6">

            <div class="card-shell section-card h-100">

                <div class="section-title">
                    <div>
                        <h2>Terms & Conditions</h2>
                        <p>Contract terms.</p>
                    </div>
                </div>

                <div class="border rounded p-3 bg-light">

                    <?php if (!empty($contract['terms_conditions'])): ?>

                        <?= nl2br(e($contract['terms_conditions'])) ?>

                    <?php else: ?>

                        <span class="text-muted">
                            No terms and conditions.
                        </span>

                    <?php endif; ?>

                </div>

            </div>

        </div>



        <!-- Note -->

        <div class="col-lg-6">

            <div class="card-shell section-card h-100">

                <div class="section-title">
                    <div>
                        <h2>Note</h2>
                        <p>Additional remarks.</p>
                    </div>
                </div>

               <div class="border rounded p-3 bg-light">

                    <?php if (!empty($contract['note'])): ?>

                        <?= nl2br(e($contract['note'])) ?>

                    <?php else: ?>

                        <span class="text-muted">
                            No notes.
                        </span>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>
</div>


<?php require __DIR__ . '/../includes/footer.php'; ?>

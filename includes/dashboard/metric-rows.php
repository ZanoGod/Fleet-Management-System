<?php

declare(strict_types=1);

$metricCounts = $metricCounts ?? [];
$metricTotal = max(1, array_sum($metricCounts));
?>

<?php foreach ($metricCounts as $label => $count): ?>
    <div class="metric-row">
        <div>
            <strong><?= e((string) $label) ?></strong>
            <div class="bar-track">
                <div class="bar-fill" style="width: <?= e((string) round(($count / $metricTotal) * 100, 1)) ?>%"></div>
            </div>
        </div>
        <span><?= e((string) $count) ?></span>
    </div>
<?php endforeach; ?>

<?php
// Check if package ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: one_day_tour.php");
    exit;
}

$package_id = intval($_GET['id']);

// Redirect to the main package detail page
header("Location: ../tour_packages/package_detail.php?id=" . $package_id);
exit;
?>
<?php
// Redirect to the main package detail page with the ID parameter
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $package_id = $_GET['id'];
    header("Location: ../tour_packages/package_detail.php?id=$package_id");
    exit;
} else {
    // If no ID or invalid ID, redirect to special tour page
    header("Location: special_tour.php");
    exit;
}
?>
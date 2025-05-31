<?php
// Include database configuration
require_once 'admin/config.php';

// Make sure reviews table exists
$check_table = "SHOW TABLES LIKE 'reviews'";
$table_exists = mysqli_query($conn, $check_table);

if (mysqli_num_rows($table_exists) == 0) {
    // Create reviews table
    $create_table = "CREATE TABLE reviews (
        review_id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        tour_type VARCHAR(100) NOT NULL,
        rating INT(1) NOT NULL,
        review_text TEXT NOT NULL,
        photo VARCHAR(255) DEFAULT NULL,
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (review_id),
        KEY status_index (status),
        KEY rating_index (rating)
    )";
    
    mysqli_query($conn, $create_table);
}

// Fetch approved reviews from database
$reviews_query = "SELECT * FROM reviews WHERE status = 'approved' ORDER BY created_at DESC LIMIT 8";
$reviews_result = mysqli_query($conn, $reviews_query);
$approved_reviews = [];
if ($reviews_result) {
    while ($review = mysqli_fetch_assoc($reviews_result)) {
        $approved_reviews[] = $review;
    }
}

// Generate HTML for reviews
ob_start(); // Start output buffering

if (count($approved_reviews) > 0) {
    foreach ($approved_reviews as $index => $review) {
        ?>
        <div class="review-card <?php echo $index === 0 ? 'active' : ''; ?>">
            <div class="user-info">
                <div class="user-img">
                    <?php if (!empty($review['photo'])): ?>
                        <img src="images/<?php echo htmlspecialchars($review['photo']); ?>" alt="User Review Photo">
                    <?php else: ?>
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($review['name']); ?>&background=random" alt="User">
                    <?php endif; ?>
                </div>
                <div class="user-details">
                    <h3><?php echo htmlspecialchars($review['name']); ?></h3>
                    <div class="rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= $review['rating']): ?>
                                <i class="star">★</i>
                            <?php else: ?>
                                <i class="star half">★</i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            <p class="review-text">"<?php echo htmlspecialchars($review['review_text']); ?>"</p>
            <div class="tour-type"><?php echo htmlspecialchars($review['tour_type']); ?></div>
            <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
        </div>
        <?php
    }
} else {
    // Default review if no approved reviews exist
    ?>
    <div class="review-card active">
        <div class="user-info">
            <div class="user-img">
                <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="User">
            </div>
            <div class="user-details">
                <h3>David Thompson</h3>
                <div class="rating">
                    <i class="star">★</i>
                    <i class="star">★</i>
                    <i class="star">★</i>
                    <i class="star">★</i>
                    <i class="star">★</i>
                </div>
            </div>
        </div>
        <p class="review-text">"Our tour to Kandy and the cultural triangle was exceptional! The guide was knowledgeable and the accommodations were perfect. Highly recommend Adventure Travel.lk for anyone looking to explore Sri Lanka."</p>
        <div class="tour-type">Cultural Tour Package</div>
    </div>
    <?php
}

$html = ob_get_clean(); // Get the HTML and end output buffering

// Also prepare the dot navigation
ob_start();

$total_reviews = count($approved_reviews) > 0 ? count($approved_reviews) : 1;
for ($i = 0; $i < $total_reviews; $i++) { 
    ?>
    <span class="dot <?php echo $i === 0 ? 'active' : ''; ?>" onclick="showReview(<?php echo $i; ?>)"></span>
    <?php 
}

$dots_html = ob_get_clean();

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'html' => $html,
    'dots_html' => $dots_html,
    'count' => count($approved_reviews)
]); 
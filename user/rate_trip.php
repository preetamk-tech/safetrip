<?php

require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotLoggedIn();

$page_title = 'Rate Traveler';
require_once '../includes/user_header.php';

$user_id = getUserId();
$trip_id = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;
$rated_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = (int)$_POST['rating'];
    $review = mysqli_real_escape_string($conn, $_POST['review']);
    
    $conn->query("INSERT INTO trip_ratings (trip_id, rater_id, rated_user_id, rating, review) 
                  VALUES ($trip_id, $user_id, $rated_user_id, $rating, '$review')");
    
    updateTrustScore($rated_user_id);
    header("Location: my_trips.php");
    exit();
}

$user = getUserById($rated_user_id);
?>

<!-- Page Header -->
<div class="page-header-gradient">
    <div class="page-header-kicker">
        <a href="my_trips.php" class="st-btn st-btn-secondary" style="margin-bottom: 0;">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
    <h1><i class="fas fa-star"></i> Rate Your Travel Experience</h1>
    <p>Share your experience to help other travelers.</p>
</div>

<div class="st-card" style="max-width: 500px; margin: 0 auto;">
    <div class="st-card-header" style="text-align: center;">
        <h2><i class="fas fa-star"></i> Rate Your Travel Experience</h2>
        <p style="color: #667085; margin-top: 10px;">How was your trip with <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>?</p>
    </div>
    <div class="st-card-body" style="text-align: center;">
        <form method="POST">
            <div class="rating-stars" id="ratingStars">
                <i class="fas fa-star star" data-rating="1"></i>
                <i class="fas fa-star star" data-rating="2"></i>
                <i class="fas fa-star star" data-rating="3"></i>
                <i class="fas fa-star star" data-rating="4"></i>
                <i class="fas fa-star star" data-rating="5"></i>
            </div>
            <input type="hidden" name="rating" id="ratingValue" required>
            
            <div style="margin: 25px 0; text-align: left;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;"><i class="fas fa-comment"></i> Write a Review (Optional)</label>
                <textarea name="review" rows="4" placeholder="Share your experience with this traveler..." style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 12px; font-size: 0.95rem; resize: vertical;"></textarea>
            </div>
            
            <button type="submit" class="st-btn st-btn-primary" style="margin-right: 10px;">
                <i class="fas fa-check"></i> Submit Rating
            </button>
            <a href="my_trips.php" class="st-btn st-btn-secondary">
                <i class="fas fa-forward"></i> Skip
            </a>
        </form>
    </div>
</div>

<?php require_once '../includes/user_footer.php'; ?>

<script>
    const stars = document.querySelectorAll('.star');
    const ratingInput = document.getElementById('ratingValue');
    
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.dataset.rating;
            ratingInput.value = rating;
            
            stars.forEach(s => {
                if(s.dataset.rating <= rating) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        });
    });
</script>

<style>
.rating-stars { display: flex; gap: 10px; justify-content: center; margin: 20px 0; }
.star { font-size: 2.5rem; cursor: pointer; color: #e5e7eb; transition: color 0.3s; }
.star:hover { color: #f59e0b; }
.star.active { color: #f59e0b; }
</style>
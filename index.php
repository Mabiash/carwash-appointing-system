

<?php require_once __DIR__ . '/includes/header.php'; ?>
<div class="hero">
    <div class="container">
        <h1 class="display-4">Sparkle Your Ride</h1>
        <p class="lead">Skip the long wait lines. Book your car wash appointment online and get your vehicle shining in no time!</p>
        <a href="<?php echo is_logged_in() ? '/carwash-appoinment/user/book.php' : '/carwash-appoinment/auth/register.php'; ?>" class="btn btn-light btn-lg px-4 py-2">
            <?php echo is_logged_in() ? 'Book Now' : 'Get Started'; ?>
        </a>
    </div>
</div>

<div class="container">
    <div class="row text-center mb-5">
        <div class="col-md-10 mx-auto">
            <h2 class="fw-bold mb-4">Why Choose SparkleWash?</h2>
            <p class="lead text-muted">We provide professional car wash services with cutting-edge technology and eco-friendly products.</p>
        </div>
    </div>
    
    <div class="row mb-5">
        <div class="col-md-4 mb-4">
            <div class="text-center">
                <div class="feature-icon mx-auto">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 class="h5 mb-3">Save Time</h3>
                <p class="text-muted">Book online and skip the wait. Your time is valuable, and we respect that.</p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="text-center">
                <div class="feature-icon mx-auto">
                    <i class="fas fa-medal"></i>
                </div>
                <h3 class="h5 mb-3">Quality Service</h3>
                <p class="text-muted">Our experienced team delivers exceptional results for every vehicle.</p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="text-center">
                <div class="feature-icon mx-auto">
                    <i class="fas fa-leaf"></i>
                </div>
                <h3 class="h5 mb-3">Eco-Friendly</h3>
                <p class="text-muted">We use environmentally safe products and water-saving techniques.</p>
            </div>
        </div>
    </div>
    
    <div class="row justify-content-center mb-5">
        <div class="col-md-10">
            <div class="card bg-light">
                <div class="card-body p-4 text-center">
                    <h3 class="mb-3">How It Works</h3>
                    <div class="row">
                        <div class="col-md-4 mb-4 mb-md-0">
                            <div class="bg-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                                <span class="h4 mb-0">1</span>
                            </div>
                            <h4 class="h6">Register an account</h4>
                            <p class="small text-muted">Create your account in less than a minute</p>
                        </div>
                        <div class="col-md-4 mb-4 mb-md-0">
                            <div class="bg-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                                <span class="h4 mb-0">2</span>
                            </div>
                            <h4 class="h6">Select service & time</h4>
                            <p class="small text-muted">Choose from our range of services and available time slots</p>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                                <span class="h4 mb-0">3</span>
                            </div>
                            <h4 class="h6">Enjoy the service</h4>
                            <p class="small text-muted">Arrive at your scheduled time and we'll take care of the rest</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-5">
        <div class="col-md-12 text-center mb-4">
            <h2 class="fw-bold">Our Popular Services</h2>
            <p class="text-muted">Choose from our range of professional car wash services</p>
        </div>
        
        <?php
        $stmt = $pdo->query("SELECT * FROM services WHERE active = 1 LIMIT 3");
        $services = $stmt->fetchAll();
        
        foreach ($services as $service):
        ?>
        <div class="col-md-4 mb-4">
            <div class="card service-card h-100">
                <img src="<?php echo $service['image'] ?? 'default-service.jpg'; ?>" class="card-img-top" alt="<?php echo $service['name']; ?>">
                <div class="card-body">
                    <h3 class="card-title h5"><?php echo $service['name']; ?></h3>
                    <p class="card-text text-muted"><?php echo $service['description']; ?></p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="service-price">₱<?php echo number_format($service['price'], 2); ?></span>
                        <span class="service-duration"><i class="far fa-clock me-1"></i><?php echo $service['duration']; ?> min</span>
                    </div>
                </div>
                <div class="card-footer bg-white border-top-0">
                    <a href="<?php echo is_logged_in() ? 'user/book.php?service_id=' . $service['id'] : '/auth/login.php'; ?>" class="btn btn-primary w-100">Book Now</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="row justify-content-center mb-5">
        <div class="col-md-10">
            <div class="card bg-primary text-white">
                <div class="card-body p-4 text-center">
                    <h3 class="mb-3">Ready for a Sparkling Clean Car?</h3>
                    <p>Experience the SparkleWash difference today. Book your appointment in just a few clicks.</p>
                    <a href="<?php echo is_logged_in() ? 'user/book.php' : '/carwash-appoinment/auth/register.php'; ?>" class="btn btn-light">
                        <?php echo is_logged_in() ? 'Book Appointment' : 'Sign Up & Book'; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
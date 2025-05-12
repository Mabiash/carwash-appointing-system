<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-8 mx-auto text-center">
            <h1 class="fw-bold mb-3">Our Services</h1>
            <p class="lead text-muted">Discover our comprehensive range of car wash and detailing services.</p>
        </div>
    </div>
    
    <div class="row">
        <?php
        $stmt = $pdo->query("SELECT * FROM services WHERE active = 1");
        $services = $stmt->fetchAll();
        
        foreach ($services as $service):
        ?>
        <div class="col-md-6 col-lg-4 mb-4">
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
                    <a href="<?php echo is_logged_in() ? '/user/book.php?service_id=' . $service['id'] : '/carwash-appoinment/auth/login.php'; ?>" class="btn btn-primary w-100">Book Now</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="row mt-4 mb-5">
        <div class="col-md-10 mx-auto">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title mb-4">Service Information</h3>
                    
                    <div class="accordion" id="serviceAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                    What's included in each service?
                                </button>
                            </h2>
                            <div id="collapse1" class="accordion-collapse collapse show" data-bs-parent="#serviceAccordion">
                                <div class="accordion-body">
                                    <p>Each of our services includes specific cleaning procedures tailored to different needs and budgets. Our basic wash covers exterior cleaning, while our premium packages include interior detailing, waxing, and more specialized treatments.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                    How long does each service take?
                                </button>
                            </h2>
                            <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#serviceAccordion">
                                <div class="accordion-body">
                                    <p>Service duration varies based on the package and vehicle size:</p>
                                    <ul>
                                        <li>Express Clean: 15-20 minutes</li>
                                        <li>Basic Wash: 30-40 minutes</li>
                                        <li>Deluxe Wash: 45-60 minutes</li>
                                        <li>Premium Detail: 2-3 hours</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                                    What if I need to cancel or reschedule?
                                </button>
                            </h2>
                            <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#serviceAccordion">
                                <div class="accordion-body">
                                    <p>We understand plans change. You can cancel or reschedule your appointment through your account dashboard up to 2 hours before your scheduled time without any penalty.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
init_session();
$pageTitle = 'Jeweluxe - Home';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ELEGANT JEWELRY HERO SECTION -->
<header class="jewelry-hero" style="background: linear-gradient(135deg, rgba(139, 111, 71, 0.75) 0%, rgba(168, 153, 104, 0.75) 100%), url('Video/wallpaper.jpg') center/cover no-repeat; min-height: 50vh; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden;">
  <!-- Decorative overlay -->
  <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(ellipse at center top, rgba(255,255,255,0.1) 0%, rgba(0,0,0,0.3) 100%); pointer-events: none;"></div>
  
  <!-- Centered Content -->
  <div class="container text-center text-white position-relative" style="z-index: 2; max-width: 900px;">
    <!-- Decorative element -->
    <div style="margin-bottom: 30px; opacity: 0.9;">
      <i class="fas fa-gem" style="font-size: 3rem; color: #ffd700; text-shadow: 0 2px 10px rgba(0,0,0,0.3);"></i>
    </div>
    
    <!-- Main heading -->
    <h1 style="font-size: clamp(2.5rem, 8vw, 4rem); font-weight: 800; margin-bottom: 25px; letter-spacing: -0.5px; text-shadow: 0 4px 12px rgba(0,0,0,0.3); line-height: 1.2;">
      Welcome to Jeweluxe
    </h1>
    
    <!-- Subheading -->
    <p style="font-size: clamp(1.1rem, 3vw, 1.5rem); margin-bottom: 50px; color: #f5f5f5; font-weight: 500; letter-spacing: 0.5px; text-shadow: 0 2px 8px rgba(0,0,0,0.2); line-height: 1.6;">
      Your journey to exquisite jewelry starts here!
    </p>
    
    <!-- CTA Buttons -->
    <div class="d-flex justify-content-center gap-4 flex-wrap">
      <a href="products.php" class="btn btn-lg px-5 py-3" style="background-color: #8b6f47; border: none; color: white; font-weight: 700; letter-spacing: 1.5px; font-size: 1rem; box-shadow: 0 6px 25px rgba(0,0,0,0.25); transition: all 0.3s ease; border-radius: 8px;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 35px rgba(0,0,0,0.35)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 25px rgba(0,0,0,0.25)';">
        <i class="fas fa-shopping-bag me-2"></i>Explore Collection
      </a>
      <a href="about.php" class="btn btn-lg px-5 py-3" style="border: 2px solid white; color: white; background: transparent; font-weight: 700; letter-spacing: 1.5px; font-size: 1rem; transition: all 0.3s ease; border-radius: 8px;" onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.2)';" onmouseout="this.style.background='transparent'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
        <i class="fas fa-info-circle me-2"></i>Learn More
      </a>
    </div>
    
  </div>
</header>

<!-- PREMIUM FEATURED PRODUCTS -->
  <section id="featured" class="py-5 bg-gradient-light">
    <div class="container">
      <div class="section-header text-center mb-5">
        <div class="section-badge mb-3">
          <span class="badge bg-primary px-3 py-2 rounded-pill">
            <i class="fas fa-star me-2"></i>Featured Collection
          </span>
        </div>
        <h2 class="display-5 fw-bold mb-3">Trending Now</h2>
        <p class="lead text-muted">Discover our most sought-after pieces, handcrafted for the modern connoisseur</p>
      </div>
      
      <div class="row g-4">
        <div class="col-md-4">
          <div class="featured-product-card h-100">
            <div class="product-media position-relative overflow-hidden rounded-3">
              <video class="product-video w-100" style="height: 400px; object-fit: cover;" autoplay muted loop playsinline>
                <source src="Video/necklace.mp4" type="video/mp4">
                Your browser does not support the video tag.
              </video>
              <div class="product-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-50 opacity-0 transition">
                <div class="text-center text-white">
                </div>
              </div>
              <div class="product-badges position-absolute top-0 end-0 p-3">
                <span class="badge bg-danger me-2">New</span>
                <span class="badge bg-success">Bestseller</span>
              </div>
            </div>
            <div class="product-content p-4 bg-white rounded-bottom-3">
              <div class="product-category text-muted small mb-2">
                <i class="fas fa-gem me-1"></i>Necklaces
              </div>
              <h5 class="product-title fw-bold mb-3">Elegant Diamond Necklaces</h5>
              <p class="product-description text-muted mb-3">Exquisite necklaces featuring brilliant diamonds set in precious metals, perfect for special occasions.</p>
              <div class="product-meta d-flex justify-content-between align-items-center mb-3">
                <div class="product-price">
                  <span class="h5 text-primary fw-bold">₱12,999</span>
                  <span class="text-muted small ms-2"><del>₱15,999</del></span>
                </div>
                <div class="product-rating">
                  <div class="text-warning">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star-half-alt"></i>
                  </div>
                  <small class="text-muted">(4.8)</small>
                </div>
              </div>
              <a href="products.php?category=necklaces" class="btn btn-primary w-100 rounded-pill">
                <i class="fas fa-shopping-cart me-2"></i>Shop Necklaces
              </a>
            </div>
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="featured-product-card h-100">
            <div class="product-media position-relative overflow-hidden rounded-3">
              <video class="product-video w-100" style="height: 400px; object-fit: cover;" autoplay muted loop playsinline>
                <source src="Video/earring.mp4" type="video/mp4">
                Your browser does not support the video tag.
              </video>
              <div class="product-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-50 opacity-0 transition">
                <div class="text-center text-white">
                </div>
              </div>
              <div class="product-badges position-absolute top-0 end-0 p-3">
                <span class="badge bg-warning text-dark">Limited</span>
              </div>
            </div>
            <div class="product-content p-4 bg-white rounded-bottom-3">
              <div class="product-category text-muted small mb-2">
                <i class="fas fa-gem me-1"></i>Earrings
              </div>
              <h5 class="product-title fw-bold mb-3">Luxury Gold Earrings</h5>
              <p class="product-description text-muted mb-3">Stunning gold earrings that add elegance to any ensemble, crafted with meticulous attention to detail.</p>
              <div class="product-meta d-flex justify-content-between align-items-center mb-3">
                <div class="product-price">
                  <span class="h5 text-primary fw-bold">₱8,499</span>
                  <span class="text-muted small ms-2"><del>₱10,999</del></span>
                </div>
                <div class="product-rating">
                  <div class="text-warning">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                  </div>
                  <small class="text-muted">(5.0)</small>
                </div>
              </div>
              <a href="products.php?category=earrings" class="btn btn-primary w-100 rounded-pill">
                <i class="fas fa-shopping-cart me-2"></i>Shop Earrings
              </a>
            </div>
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="featured-product-card h-100">
            <div class="product-media position-relative overflow-hidden rounded-3">
              <video class="product-video w-100" style="height: 400px; object-fit: cover;" autoplay muted loop playsinline>
                <source src="Video/bracelet.mp4" type="video/mp4">
                Your browser does not support the video tag.
              </video>
              <div class="product-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-50 opacity-0 transition">
                <div class="text-center text-white">
                </div>
              </div>
              <div class="product-badges position-absolute top-0 end-0 p-3">
                <span class="badge bg-info">Trending</span>
              </div>
            </div>
            <div class="product-content p-4 bg-white rounded-bottom-3">
              <div class="product-category text-muted small mb-2">
                <i class="fas fa-gem me-1"></i>Bracelets
              </div>
              <h5 class="product-title fw-bold mb-3">Designer Silver Bracelets</h5>
              <p class="product-description text-muted mb-3">Sophisticated silver bracelets that blend contemporary design with timeless elegance.</p>
              <div class="product-meta d-flex justify-content-between align-items-center mb-3">
                <div class="product-price">
                  <span class="h5 text-primary fw-bold">₱6,999</span>
                  <span class="text-muted small ms-2"><del>₱8,999</del></span>
                </div>
                <div class="product-rating">
                  <div class="text-warning">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="far fa-star"></i>
                  </div>
                  <small class="text-muted">(4.2)</small>
                </div>
              </div>
              <a href="products.php?category=bracelets" class="btn btn-primary w-100 rounded-pill">
                <i class="fas fa-shopping-cart me-2"></i>Shop Bracelets
              </a>
            </div>
          </div>
        </div>
      </div>
      
      <div class="text-center mt-5">
        <a href="products.php" class="btn btn-outline-primary btn-lg rounded-pill px-5">
          <i class="fas fa-th me-2"></i>View All Products
        </a>
      </div>
    </div>
  </section>

<!-- ECOMMERCE SERVICES SECTION -->
  <section class="py-5 bg-white">
    <div class="container">
      <div class="section-header text-center mb-5">
        <h2 class="display-5 fw-bold mb-3">Why Choose Jeweluxe</h2>
        <p class="lead text-muted">Experience premium shopping with our exclusive services</p>
      </div>
      
      <div class="row g-4">
        <div class="col-md-3">
          <div class="service-card text-center p-4 rounded-3 bg-light bg-gradient h-100">
            <div class="service-icon mb-3">
              <div class="icon-wrapper bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                <i class="fas fa-shipping-fast fa-2x text-primary"></i>
              </div>
            </div>
            <h5 class="service-title fw-bold mb-3">Free Shipping</h5>
            <p class="service-text text-muted">Enjoy complimentary shipping on all orders over ₱5,000</p>
          </div>
        </div>
        
        <div class="col-md-3">
          <div class="service-card text-center p-4 rounded-3 bg-light bg-gradient h-100">
            <div class="service-icon mb-3">
              <div class="icon-wrapper bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                <i class="fas fa-shield-alt fa-2x text-success"></i>
              </div>
            </div>
            <h5 class="service-title fw-bold mb-3">Secure Payment</h5>
            <p class="service-text text-muted">100% secure transactions with encrypted payment processing</p>
          </div>
        </div>
        
        <div class="col-md-3">
          <div class="service-card text-center p-4 rounded-3 bg-light bg-gradient h-100">
            <div class="service-icon mb-3">
              <div class="icon-wrapper bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                <i class="fas fa-undo fa-2x text-warning"></i>
              </div>
            </div>
            <h5 class="service-title fw-bold mb-3">Easy Returns</h5>
            <p class="service-text text-muted">30-day hassle-free return policy on all purchases</p>
          </div>
        </div>
        
        <div class="col-md-3">
          <div class="service-card text-center p-4 rounded-3 bg-light bg-gradient h-100">
            <div class="service-icon mb-3">
              <div class="icon-wrapper bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                <i class="fas fa-headset fa-2x text-info"></i>
              </div>
            </div>
            <h5 class="service-title fw-bold mb-3">24/7 Support</h5>
            <p class="service-text text-muted">Round-the-clock customer service for all your needs</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- TRUST BADGES SECTION -->
  <section class="py-4 bg-light border-top">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-8">
          <div class="trust-badges d-flex flex-wrap align-items-center gap-4">
            <div class="trust-item">
              <i class="fas fa-certificate text-primary me-2"></i>
              <span class="small">Authentic Products</span>
            </div>
            <div class="trust-item">
              <i class="fas fa-award text-primary me-2"></i>
              <span class="small">Award Winning</span>
            </div>
            <div class="trust-item">
              <i class="fas fa-users text-primary me-2"></i>
              <span class="small">50K+ Happy Customers</span>
            </div>
            <div class="trust-item">
              <i class="fas fa-star text-primary me-2"></i>
              <span class="small">4.9/5 Rating</span>
            </div>
          </div>
        </div>
        <div class="col-md-4 text-md-end">
          <div class="payment-methods">
            <span class="small text-muted me-2">We Accept:</span>
            <i class="fab fa-cc-visa text-primary me-1"></i>
            <i class="fab fa-cc-mastercard text-primary me-1"></i>
            <i class="fab fa-cc-amex text-primary me-1"></i>
            <i class="fab fa-paypal text-primary"></i>
          </div>
        </div>
      </div>
    </div>
  </section>

<?php include __DIR__ . '/includes/footer.php'; ?>

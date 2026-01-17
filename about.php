<?php
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
init_session();
$pageTitle = 'Jeweluxe - About';
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
        About Jeweluxe
      </h1>
      
      <!-- Subheading -->
      <p style="font-size: clamp(1.1rem, 3vw, 1.5rem); margin-bottom: 50px; color: #f5f5f5; font-weight: 500; letter-spacing: 0.5px; text-shadow: 0 2px 8px rgba(0,0,0,0.2); line-height: 1.6;">
        Discover timeless jewelry for every occasion
      </p>
      
      <!-- CTA Button -->
      <div class="d-flex justify-content-center gap-4 flex-wrap">
        <a href="#about-section" class="btn btn-lg px-5 py-3" style="background-color: #8b6f47; border: none; color: white; font-weight: 700; letter-spacing: 1.5px; font-size: 1rem; box-shadow: 0 6px 25px rgba(0,0,0,0.25); transition: all 0.3s ease; border-radius: 8px;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 35px rgba(0,0,0,0.35)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 25px rgba(0,0,0,0.25)';">
          <i class="fas fa-info-circle me-2"></i>Learn Our Story
        </a>
      </div>
    </div>
  </header>

  <!-- ABOUT CONTENT -->
  <main class="container py-5">
    <!-- About Jeweluxe Section -->
    <section class="row mb-5">
      <div class="col-lg-6">
        <h2 class="mb-4">About Jeweluxe</h2>
        <p class="lead">Welcome to Jeweluxe, where elegance meets craftsmanship. We are passionate about creating exquisite jewelry pieces that celebrate life's most precious moments.</p>
        <p>Founded with a vision to bring timeless beauty to every jewelry lover, Jeweluxe specializes in handcrafted pieces that combine traditional techniques with contemporary design. Each piece in our collection tells a story of artistry, quality, and attention to detail.</p>
      </div>
      <div class="col-lg-6">
        <img src="Video/wallpaper.jpg" alt="Jeweluxe Collection" class="img-fluid rounded shadow">
      </div>
    </section>

    <!-- Our Product Collections -->
    <section class="mb-5">
      <h2 class="text-center mb-5">Our Exquisite Collections</h2>
      
      <!-- Necklaces Collection -->
      <div class="row mb-5 align-items-center">
        <div class="col-md-6">
          <div class="text-center">
            <div class="ratio ratio-16x9">
              <img src="image/necklotus.jpg" alt="Lotus Necklace" class="img-fluid rounded mb-3" style="object-fit: cover; width: 100%; height: 100%;">
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <h3 class="text-primary">Necklace Collection</h3>
          <p><strong>Our signature necklaces are designed to make every moment special.</strong></p>
          <ul class="list-unstyled">
            <li><strong>🌸 Lotus Necklace (₱2,345.00)</strong> - Inspired by the lotus flower's symbolism of purity and enlightenment, this delicate piece features intricate lotus petal details crafted in premium gold-tone metal.</li>
            <br>
            <li><strong>🤍 Pearl Necklace (₱1,600.00)</strong> - Classic elegance meets modern sophistication. Our pearl necklace features lustrous freshwater pearls perfectly matched for color and size, creating a timeless piece perfect for any occasion.</li>
            <br>
            <li><strong>🔗 Necklace Tied Knot (₱2,499.00)</strong> - A contemporary design representing eternal bonds and infinite love. The tied knot pendant symbolizes unbreakable connections and is perfect for gifting to someone special.</li>
          </ul>
        </div>
      </div>

      <!-- Bracelets Collection -->
      <div class="row mb-5 align-items-center">
        <div class="col-md-6">
          <h3 class="text-primary">Bracelet Collection</h3>
          <p><strong>Elegant wrist accessories that complement your unique style.</strong></p>
          <ul class="list-unstyled">
            <li><strong>🌸 Lotus Bracelet (₱2,345.00)</strong> - Matching our popular lotus necklace, this bracelet brings the same spiritual symbolism and intricate craftsmanship to your wrist. Perfect for stacking or wearing alone.</li>
            <br>
            <li><strong>🤍 Pearl Bracelet (₱1,600.00)</strong> - Sophisticated and versatile, featuring the same premium freshwater pearls as our necklace collection. Ideal for both casual and formal occasions.</li>
            <br>
            <li><strong>🔗 Tied Knot Bracelet (₱1,800.00)</strong> - A delicate interpretation of our signature knot design, this bracelet adds a touch of meaningful elegance to any outfit while representing the bonds that matter most.</li>
          </ul>
        </div>
        <div class="col-md-6">
          <div class="text-center">
            <div class="ratio ratio-16x9">
              <img src="image/lotusbrace.jpg" alt="Lotus Bracelet" class="img-fluid rounded mb-3" style="object-fit: cover; width: 100%; height: 100%;">
            </div>
          </div>
        </div>
      </div>

      <!-- Earrings Collection -->
      <div class="row mb-5 align-items-center">
        <div class="col-md-6">
          <div class="text-center">
            <div class="ratio ratio-16x9">
              <img src="image/earlotus.jpg" alt="Lotus Earrings" class="img-fluid rounded mb-3" style="object-fit: cover; width: 100%; height: 100%;">
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <h3 class="text-primary">Earring Collection</h3>
          <p><strong>Complete your look with our stunning earring designs.</strong></p>
          <ul class="list-unstyled">
            <li><strong>🌸 Lotus Earrings (₱2,345.00)</strong> - Delicate drop earrings featuring our signature lotus motif. These lightweight pieces add elegance without overwhelming your natural beauty.</li>
            <br>
            <li><strong>🤍 Pearl Earrings (₱1,600.00)</strong> - Classic pearl studs that never go out of style. These perfectly matched pearls offer understated elegance suitable for everyday wear or special occasions.</li>
            <br>
            <li><strong>🔗 Tied Earrings (₱1,600.00)</strong> - Unique knot-inspired earrings that add a modern twist to traditional jewelry. Perfect for those who appreciate contemporary design with meaningful symbolism.</li>
          </ul>
        </div>
      </div>
    </section>

    <!-- Quality & Craftsmanship -->
    <section class="mb-5">
      <div class="row">
        <div class="col-lg-12">
          <h2 class="text-center mb-4">Quality & Craftsmanship</h2>
          <div class="row">
            <div class="col-md-4 text-center mb-4">
              <div class="p-4 border rounded">
                <h4 class="text-primary">Premium Materials</h4>
                <p>We use only the finest materials including genuine freshwater pearls, high-quality metals, and carefully selected gemstones to ensure lasting beauty and durability.</p>
              </div>
            </div>
            <div class="col-md-4 text-center mb-4">
              <div class="p-4 border rounded">
                <h4 class="text-primary">Artisan Crafted</h4>
                <p>Each piece is carefully handcrafted by skilled artisans who pay attention to every detail, ensuring that your jewelry is not just beautiful, but also unique.</p>
              </div>
            </div>
            <div class="col-md-4 text-center mb-4">
              <div class="p-4 border rounded">
                <h4 class="text-primary">Timeless Design</h4>
                <p>Our designs blend classic elegance with contemporary style, creating pieces that will remain fashionable and cherished for years to come.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Why Choose Jeweluxe -->
    <section class="mb-5 bg-light p-5 rounded">
      <h2 class="text-center mb-4">Why Choose Jeweluxe?</h2>
      <div class="row">
        <div class="col-lg-6">
          <ul class="list-unstyled">
            <li class="mb-3"><strong>Exceptional Quality:</strong> Every piece undergoes rigorous quality control to meet our high standards.</li>
            <li class="mb-3"><strong>Perfect for Gifting:</strong> Our jewelry comes beautifully packaged, making it ideal for special occasions.</li>
            <li class="mb-3"><strong>Secure Shopping:</strong> Safe and secure online shopping experience with multiple payment options.</li>
          </ul>
        </div>
        <div class="col-lg-6">
          <ul class="list-unstyled">
            <li class="mb-3"><strong>Fast Delivery:</strong> Quick and reliable shipping to get your jewelry to you safely and promptly.</li>
            <li class="mb-3"><strong>Customer Support:</strong> Dedicated customer service team ready to assist you with any questions.</li>
            <li class="mb-3"><strong>Satisfaction Guarantee:</strong> We stand behind our products with a comprehensive satisfaction guarantee.</li>
          </ul>
        </div>
      </div>
    </section>

    <!-- Call to Action -->
    <section class="text-center">
      <h2 class="mb-4">Ready to Find Your Perfect Piece?</h2>
      <p class="lead mb-4">Explore our complete collection and discover the jewelry that speaks to your heart.</p>
      <a href="products.php" class="btn btn-primary btn-lg me-3">Shop Our Collection</a>
      <a href="contactus.php" class="btn btn-outline-primary btn-lg">Contact Us</a>
    </section>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>

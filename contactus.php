<?php
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
init_session();
$pageTitle = 'Jeweluxe - Contact Us';
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
      Get In Touch
    </h1>
    
    <!-- Subheading -->
    <p style="font-size: clamp(1.1rem, 3vw, 1.5rem); margin-bottom: 50px; color: #f5f5f5; font-weight: 500; letter-spacing: 0.5px; text-shadow: 0 2px 8px rgba(0,0,0,0.2); line-height: 1.6;">
      We'd love to hear from you. Contact us today!
    </p>
    
    <!-- CTA Button -->
    <div class="d-flex justify-content-center gap-4 flex-wrap">
      <a href="#contact-section" class="btn btn-lg px-5 py-3" style="background-color: #8b6f47; border: none; color: white; font-weight: 700; letter-spacing: 1.5px; font-size: 1rem; box-shadow: 0 6px 25px rgba(0,0,0,0.25); transition: all 0.3s ease; border-radius: 8px;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 35px rgba(0,0,0,0.35)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 25px rgba(0,0,0,0.25)';">
        <i class="fas fa-envelope me-2"></i>Send Message
      </a>
    </div>
  </div>
</header>

<style>
    /* Custom focus highlighting */
    .form-control:focus {
      border-color: #007bff !important;
      box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
    }
    
    /* Required field validation styling */
    .form-control.is-invalid {
      border-color: #dc3545;
    }
    
    .invalid-feedback {
      display: block;
      width: 100%;
      margin-top: 0.25rem;
      font-size: 0.875em;
      color: #dc3545;
    }
    
    /* Remove browser validation styling */
    .form-control:invalid {
      box-shadow: none !important;
    }
    
    .form-control:valid {
      box-shadow: none !important;
    }
    
    /* Hide browser validation messages */
    .form-control::-webkit-validation-bubble {
      display: none !important;
    }
    
    /* Contact info styling */
    .contact-info-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-radius: 15px;
      padding: 2rem;
      margin-bottom: 2rem;
    }
    
    .contact-info-card i {
      font-size: 2rem;
      margin-bottom: 1rem;
      color: #ffd700;
    }
    
    .contact-form-card {
      background: white;
      border-radius: 15px;
      padding: 2rem;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
  </style>

  <section class="container py-5" id="contact-section">
    <div class="row">
    
      <div class="col-lg-4 mb-4">
        <div class="contact-info-card text-center">
          <i class="fas fa-envelope"></i>
          <h4>Email Us</h4>
          <p class="mb-3">Send us an email and we'll respond within 24 hours</p>
          <a href="mailto:jeweluxe@gmail.com" class="btn btn-warning">
            <i class="fas fa-envelope me-2"></i>jeweluxe@gmail.com
          </a>
        </div>
        
        <div class="contact-info-card text-center">
          <i class="fas fa-clock"></i>
          <h4>Business Hours</h4>
          <p class="mb-2"><strong>Monday - Friday:</strong> 9:00 AM - 6:00 PM</p>
          <p class="mb-2"><strong>Saturday:</strong> 10:00 AM - 4:00 PM</p>
          <p class="mb-0"><strong>Sunday:</strong> Closed</p>
        </div>
        
        <div class="contact-info-card text-center">
          <i class="fas fa-headset"></i>
          <h4>Customer Support</h4>
          <p class="mb-3">Our dedicated team is here to help you with any questions about our jewelry collection</p>
          <p class="mb-0"><strong>Response Time:</strong> Within 24 hours</p>
        </div>
      </div>
      
 
      <div class="col-lg-8">
        <div class="contact-form-card">
          <h3 class="mb-4 text-center">
            <i class="fas fa-paper-plane me-2"></i>Send us a Message
          </h3>
          
          <?php if (isset($_GET['success']) && $_GET['success'] === 'sent'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="fas fa-check-circle me-2"></i>
              <strong>Message sent successfully!</strong> We'll get back to you within 24 hours.
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>
          
          <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="fas fa-exclamation-circle me-2"></i>
              <strong>Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>
          
          <form action="process_contact.php" method="POST" id="contactForm" novalidate>
            <div class="mb-3">
              <label for="name" class="form-label">Full Name *</label>
              <input type="text" id="name" name="name" class="form-control" 
                     value="<?php echo (isset($user['firstname']) && isset($user['lastname'])) ? htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) : ''; ?>" 
                     required minlength="2">
              <div class="invalid-feedback">Please enter your full name (at least 2 characters).</div>
            </div>

            <div class="mb-3">
              <label for="email" class="form-label">Email *</label>
              <input type="email" id="email" name="email" class="form-control" 
                     value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>" 
                     required>
              <div class="invalid-feedback">Please enter a valid email address.</div>
            </div>

            <div class="mb-3">
              <label for="subject" class="form-label">Subject *</label>
              <select id="subject" name="subject" class="form-select" required>
                <option value="">Choose...</option>
                <option value="Order Inquiry">Order Inquiry</option>
                <option value="Product Question">Product Question</option>
                <option value="Payment Issue">Payment Issue</option>
                <option value="Shipping/Delivery">Shipping/Delivery</option>
                <option value="Return/Refund">Return/Refund</option>
                <option value="Technical Support">Technical Support</option>
                <option value="Feedback">Feedback</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="message" class="form-label">Message *</label>
              <textarea id="message" name="message" class="form-control" rows="6" required minlength="10" 
                        placeholder="Please describe your inquiry in detail..."></textarea>
              <small class="text-muted">Minimum 10 characters</small>
            </div>

            <div class="text-center">
              <button type="submit" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-paper-plane me-2"></i>Send Message
              </button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </section>

<script>
// Custom form validation for contact form
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    let isValid = true;
    const form = this;
    
    // Clear previous validation states
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    
    // Validate name
    const nameInput = document.getElementById('name');
    if (nameInput.value.trim().length < 2) {
        nameInput.classList.add('is-invalid');
        isValid = false;
    }
    
    // Validate email
    const emailInput = document.getElementById('email');
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(emailInput.value.trim())) {
        emailInput.classList.add('is-invalid');
        isValid = false;
    }
    
    // Validate subject
    const subjectInput = document.getElementById('subject');
    if (!subjectInput.value) {
        subjectInput.classList.add('is-invalid');
        isValid = false;
    }
    
    // Validate message
    const messageInput = document.getElementById('message');
    if (messageInput.value.trim().length < 10) {
        messageInput.classList.add('is-invalid');
        isValid = false;
    }
    
    if (isValid) {
        form.submit();
    } else {
        // Scroll to first error
        const firstError = form.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstError.focus();
        }
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

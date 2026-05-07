<?php
/**
 * KATSS - Kirehe Adventist Technical Secondary School
 * Main Website - All pages combined with dynamic image display
 */

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Fetch events
try {
    $stmt = $db->prepare("SELECT * FROM events WHERE status = 'published' ORDER BY event_date DESC LIMIT 8");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $events = []; }

// Fetch featured events
try {
    $stmt = $db->prepare("SELECT * FROM events WHERE status = 'published' AND is_featured = 1 ORDER BY event_date DESC LIMIT 3");
    $stmt->execute();
    $featured_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $featured_events = []; }

// Fetch gallery items
try {
    $stmt = $db->prepare("SELECT * FROM gallery_items WHERE status = 'active' ORDER BY created_at DESC LIMIT 12");
    $stmt->execute();
    $gallery_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $gallery_items = []; }

/**
 * Resolve image path from database to a working URL
 * DB stores: uploads/events/filename.jpg
 * Public accesses: admin/uploads/events/filename.jpg
 */
function resolve_image(string $raw, string $fallback = ''): string {
    $raw = trim($raw);
    if ($raw === '') return $fallback;
    
    // External URLs pass through unchanged
    if (preg_match('#^https?://#i', $raw)) {
        return htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
    }
    
    $path = ltrim($raw, '/');
    $path = preg_replace('#^(\.\./|\./)+#', '', $path);
    
    // Try multiple possible paths
    $possiblePaths = [
        'admin/' . $path,
        $path,
        'admin/' . ltrim($path, 'admin/'),
    ];
    
    foreach ($possiblePaths as $tryPath) {
        $fullPath = __DIR__ . '/' . $tryPath;
        if (file_exists($fullPath)) {
            return htmlspecialchars($tryPath, ENT_QUOTES, 'UTF-8');
        }
    }
    
    return $fallback;
}

function event_image(array $event): string {
    $fallback = 'https://placehold.co/800x450/003366/D4AF37?text=KATSS+Event';
    foreach (['image_url', 'image_path', 'thumbnail_path'] as $col) {
        if (!empty($event[$col])) return resolve_image($event[$col], $fallback);
    }
    return $fallback;
}

function gallery_image(array $item): string {
    $title = urlencode($item['title'] ?? 'Gallery');
    $fallback = "https://placehold.co/600x400/003366/D4AF37?text={$title}";
    // Check media_url FIRST since that's what your database uses
    foreach (['media_url', 'file_path', 'thumbnail_path'] as $col) {
        if (!empty($item[$col])) {
            return resolve_image($item[$col], $fallback);
        }
    }
    return $fallback;
}

function gallery_video(array $item): string {
    $fallback = '';
    foreach (['media_url', 'file_path'] as $col) {
        if (!empty($item[$col])) {
            return resolve_image($item[$col], $fallback);
        }
    }
    return $fallback;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kirehe Adventist Technical Secondary School - Empowering students with technical skills and strong moral values.">
    <link rel="icon" type="image/jpeg" href="../images/logo.jpeg">
    <title>KATSS — Kirehe Adventist Technical Secondary School</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- TOP BAR -->
<div class="topbar">
  <div class="container">
    <div class="topbar-contact">
      <a href="mailto:katsapapen@gmail.com"><i class="bi bi-envelope-fill"></i>katsapapen@gmail.com</a>
      <a href="tel:+250788416574"><i class="bi bi-telephone-fill"></i>+250 788 416 574</a>
    </div>
    <div class="topbar-social">
      <a href="https://facebook.com" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
      <a href="https://youtube.com" target="_blank" rel="noopener" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
      <a href="https://twitter.com" target="_blank" rel="noopener" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
      <a href="https://instagram.com" target="_blank" rel="noopener" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
    </div>
  </div>
</div>

<!-- HEADER -->
<header class="site-header transparent" id="siteHeader">
  <div class="navbar">
    <div class="container">
      <a class="brand" href="#home" data-page="home">
        <div class="brand-logo"><img src="../images/logo.jpeg" alt="KATSS" onerror="this.src='https://placehold.co/46x46/003366/D4AF37?text=K';"></div>
        <div class="brand-text"><span class="brand-name">KATSS</span><span class="brand-tagline">Dream · Believe · Achieve</span></div>
      </a>
      <nav class="main-nav">
        <ul class="nav-list">
          <li><a href="#home" data-page="home" class="active"><i class="bi bi-house-door-fill"></i> Home</a></li>
          <li><a href="#about" data-page="about"><i class="bi bi-info-circle-fill"></i> About</a></li>
          <li><a href="#academics" data-page="academics"><i class="bi bi-book-fill"></i> Academics</a></li>
          <li><a href="#news-events" data-page="news-events"><i class="bi bi-megaphone-fill"></i> News</a></li>
          <li><a href="#student-life" data-page="student-life"><i class="bi bi-people-fill"></i> Student Life</a></li>
          <li><a href="#gallery" data-page="gallery"><i class="bi bi-images"></i> Gallery</a></li>
          <li><a href="#admissions" data-page="admissions"><i class="bi bi-person-check-fill"></i> Admissions</a></li>
          <li><a href="#contact" data-page="contact" class="nav-cta"><i class="bi bi-headset"></i> Contact</a></li>
        </ul>
      </nav>
      <button class="hamburger" id="hamburger" aria-label="Menu"><span></span><span></span><span></span></button>
    </div>
  </div>
</header>

<!-- MOBILE NAV -->
<div class="nav-overlay" id="navOverlay"></div>
<nav class="mobile-nav" id="mobileNav">
  <div class="mobile-nav-header">
    <div class="brand" style="gap:10px;"><div class="brand-logo" style="width:36px;height:36px;"><img src="../images/logo.jpeg" alt="KATSS"></div><span class="brand-name" style="color:var(--white);">KATSS</span></div>
    <button class="mobile-close" id="mobileClose">&times;</button>
  </div>
  <ul class="mobile-nav-list">
    <li><a href="#home" data-page="home" class="active"><i class="bi bi-house-door-fill"></i> Home</a></li>
    <li><a href="#about" data-page="about"><i class="bi bi-info-circle-fill"></i> About Us</a></li>
    <li><a href="#academics" data-page="academics"><i class="bi bi-book-fill"></i> Academics</a></li>
    <li><a href="#news-events" data-page="news-events"><i class="bi bi-megaphone-fill"></i> News & Events</a></li>
    <li><a href="#student-life" data-page="student-life"><i class="bi bi-people-fill"></i> Student Life</a></li>
    <li><a href="#gallery" data-page="gallery"><i class="bi bi-images"></i> Gallery</a></li>
    <li><a href="#admissions" data-page="admissions"><i class="bi bi-person-check-fill"></i> Admissions</a></li>
    <li><a href="#contact" data-page="contact"><i class="bi bi-headset"></i> Contact Us</a></li>
  </ul>
  <div class="mobile-nav-footer"><a href="#admissions" data-page="admissions"><i class="bi bi-arrow-right-circle"></i> Apply Now</a></div>
</nav>

<main>

<!-- ===== HOME PAGE ===== -->
<section id="home" class="page active">
  <div class="hero">
    <div class="hero-bg"></div>
    <div class="hero-pattern"></div>
    <div class="container hero-content">
      <div class="hero-badge fade-up"><i class="bi bi-award-fill"></i> Kirehe District, Eastern Rwanda</div>
      <h1 class="hero-title fade-up delay-1">Kirehe Adventist<br><span>Technical Secondary</span><br>School</h1>
      <p class="hero-motto fade-up delay-2">Dream · Believe · Achieve</p>
      <p class="hero-description fade-up delay-3">Equipping students with technical skills and strong moral values for a successful future in Rwanda and beyond.</p>
      <div class="hero-actions fade-up delay-4">
        <a href="#admissions" data-page="admissions" class="btn btn-primary btn-lg"><i class="bi bi-person-plus-fill"></i> Apply Now</a>
        <a href="#about" data-page="about" class="btn btn-outline btn-lg"><i class="bi bi-play-circle"></i> Learn More</a>
      </div>
      <div class="hero-stats fade-up delay-4">
        <div class="hero-stat"><div class="hero-stat-num">1,200+</div><div class="hero-stat-label">Students Enrolled</div></div>
        <div class="hero-stat"><div class="hero-stat-num">95%</div><div class="hero-stat-label">Placement Rate</div></div>
        <div class="hero-stat"><div class="hero-stat-num">6</div><div class="hero-stat-label">Technical Trades</div></div>
        <div class="hero-stat"><div class="hero-stat-num">75+</div><div class="hero-stat-label">Expert Staff</div></div>
      </div>
    </div>
    <div class="hero-scroll"><i class="bi bi-chevron-down"></i>Scroll</div>
  </div>

  <!-- Featured Events -->
  <?php if (!empty($featured_events)): ?>
  <section class="section">
    <div class="container">
      <p class="section-label">Latest Highlights</p>
      <h2 class="section-title">News & School Events</h2>
      <div class="divider"></div>
      <div class="swiper highlights-swiper">
        <div class="swiper-wrapper">
          <?php foreach ($featured_events as $event): ?>
          <div class="swiper-slide">
            <div class="highlight-card">
              <div class="aspect aspect--3-2">
                <img src="<?php echo event_image($event); ?>" 
                     alt="<?php echo htmlspecialchars($event['title']); ?>" 
                     loading="lazy"
                     onerror="this.onerror=null;this.src='https://placehold.co/800x540/002147/D4AF37?text=KATSS';">
              </div>
              <div class="highlight-content">
                <div class="highlight-date"><i class="bi bi-calendar3"></i> <?php echo date('F d, Y', strtotime($event['event_date'])); ?></div>
                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                <p><?php echo htmlspecialchars(mb_substr($event['excerpt'] ?? $event['content'], 0, 220)); ?>…</p>
                <a href="#news-events" data-page="news-events" class="btn btn-navy btn-sm">Read More <i class="bi bi-arrow-right"></i></a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="swiper-pagination" style="margin-top:20px;position:static;"></div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Programs -->
  <section class="section section--alt">
    <div class="container">
      <div class="text-center">
        <p class="section-label">What We Offer</p>
        <h2 class="section-title">Technical & Vocational Programs</h2>
        <div class="divider"></div>
      </div>
      <div class="swiper pathways-swiper">
        <div class="swiper-wrapper">
          <?php
          $programs = [
            ['img'=>'../images/1000003728.jpg','icon'=>'bi-code-slash','title'=>'Software Development','desc'=>'Master coding, database management, and app development.'],
            ['img'=>'../images/account.jpg','icon'=>'bi-wallet-fill','title'=>'Accounting','desc'=>'Financial record-keeping, auditing, tax laws, and modern accounting software.'],
            ['img'=>'../images/bdc.jpg','icon'=>'bi-hammer','title'=>'Building Construction','desc'=>'Masonry, carpentry, site supervision, and sustainable construction.'],
            ['img'=>'../images/tourism.jpeg','icon'=>'bi-globe2','title'=>'Tourism','desc'=>'Hospitality management, tour operations, and cultural sites promotion.'],
            ['img'=>'../images/1000004437.jpg','icon'=>'bi-camera-video-fill','title'=>'Multimedia','desc'=>'Graphic design, video editing, photography, and audio production.'],
            ['img'=>'../images/auto.png','icon'=>'bi-truck','title'=>'Automobile Technology','desc'=>'Diagnosis, repair, and maintenance of modern vehicle systems.'],
          ];
          foreach($programs as $p): ?>
          <div class="swiper-slide" style="height:auto;">
            <div class="program-card">
              <div class="aspect aspect--16-9"><img src="<?php echo $p['img']; ?>" alt="<?php echo $p['title']; ?>" loading="lazy" onerror="this.src='https://placehold.co/600x338/002147/D4AF37?text=<?php echo urlencode($p['title']); ?>';"></div>
              <div class="program-card-body"><div class="program-icon"><i class="bi <?php echo $p['icon']; ?>"></i></div><h3><?php echo $p['title']; ?></h3><p><?php echo $p['desc']; ?></p></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="swiper-button-next pathways-next"></div>
        <div class="swiper-button-prev pathways-prev"></div>
        <div class="swiper-pagination pathways-pagination" style="margin-top:20px;position:static;"></div>
      </div>
    </div>
  </section>

  <!-- Stats -->
  <div class="stats-strip">
    <div class="container">
      <div class="stats-grid">
        <div class="stat-item"><div class="stat-num">1,200+</div><div class="stat-label">Students Enrolled</div></div>
        <div class="stat-item"><div class="stat-num">95%</div><div class="stat-label">Placement Rate</div></div>
        <div class="stat-item"><div class="stat-num">6</div><div class="stat-label">Technical Trades</div></div>
        <div class="stat-item"><div class="stat-num">75+</div><div class="stat-label">Staff Members</div></div>
        <div class="stat-item"><div class="stat-num">2004</div><div class="stat-label">Year Established</div></div>
      </div>
    </div>
  </div>

  <!-- Testimonials -->
  <section class="section section--dark">
    <div class="container">
      <div class="text-center"><p class="section-label">Student Voices</p><h2 class="section-title">What Our Students Say</h2><div class="divider" style="margin-inline:auto;"></div></div>
      <div class="swiper testimonials-swiper">
        <div class="swiper-wrapper">
          <div class="swiper-slide"><div class="testimonial-card"><p class="testimonial-quote">The hands-on training in Software Development is incredible. I'm building real projects. KATSS has given me a career path.</p><div class="testimonial-author"><h4>Witness Fabrice</h4><p>L5, Software Development</p></div></div></div>
          <div class="swiper-slide"><div class="testimonial-card"><p class="testimonial-quote">With practical exercises and supportive teachers, I feel ready for a professional role. KATSS truly prepares you.</p><div class="testimonial-author"><h4>Muhire Dieudonne</h4><p>L5, Software Development</p></div></div></div>
          <div class="swiper-slide"><div class="testimonial-card"><p class="testimonial-quote">I love the Multimedia program! I've learned photography, video editing, and graphic design professionally.</p><div class="testimonial-author"><h4>MBWEBAZE David</h4><p>L5, Multimedia Production</p></div></div></div>
        </div>
        <div class="swiper-pagination" style="margin-top:28px;position:static;"></div>
      </div>
    </div>
  </section>

  <!-- Latest News -->
  <section class="section">
    <div class="container">
      <div class="news-strip">
        <div>
          <p class="section-label">Stay Informed</p><h2 class="section-title">Latest School News</h2><div class="divider"></div>
          <?php $latest_news = array_slice($events, 0, 4);
          if (!empty($latest_news)): foreach ($latest_news as $news): ?>
          <div class="news-item-mini"><div class="news-mini-date"><?php echo date('M d, Y', strtotime($news['event_date'])); ?></div><div class="news-mini-title"><a href="#news-events" data-page="news-events"><?php echo htmlspecialchars($news['title']); ?></a></div></div>
          <?php endforeach; else: ?>
          <div class="news-item-mini"><div class="news-mini-date">Oct 25, 2025</div><div class="news-mini-title"><a href="#news-events" data-page="news-events">Annual Tech Fair Showcase</a></div></div>
          <div class="news-item-mini"><div class="news-mini-date">Sep 15, 2025</div><div class="news-mini-title"><a href="#news-events" data-page="news-events">Community Clean-Up Drive</a></div></div>
          <?php endif; ?>
          <a href="#news-events" data-page="news-events" class="btn btn-ghost btn-sm" style="margin-top:20px;">View All News <i class="bi bi-arrow-right"></i></a>
        </div>
        <div>
          <div class="upcoming-card" style="background-image:url('../images/schoolcomp.jpg');">
            <div class="upcoming-inner"><h4>Upcoming: Open Day 2025</h4><p>Visit our campus, meet teachers, explore programs.</p><a href="#contact" data-page="contact" class="btn btn-primary btn-sm">Get Directions <i class="bi bi-geo-alt"></i></a></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Why KATSS -->
  <section class="section section--alt">
    <div class="container">
      <div class="text-center"><p class="section-label">Our Difference</p><h2 class="section-title">Why Choose KATSS?</h2><div class="divider"></div></div>
      <div class="features-grid">
        <div class="feature-card"><div class="feature-icon"><i class="bi bi-book-half"></i></div><h3>Quality Education</h3><p>World-class curriculum combining theory with practical hands-on training.</p></div>
        <div class="feature-card"><div class="feature-icon"><i class="bi bi-lightbulb"></i></div><h3>Innovation Focus</h3><p>State-of-the-art facilities equipped with modern technology.</p></div>
        <div class="feature-card"><div class="feature-icon"><i class="bi bi-people-fill"></i></div><h3>Expert Faculty</h3><p>Experienced instructors with real industry expertise.</p></div>
        <div class="feature-card"><div class="feature-icon"><i class="bi bi-briefcase"></i></div><h3>Employment Ready</h3><p>95% graduation success rate with direct placement opportunities.</p></div>
        <div class="feature-card"><div class="feature-icon"><i class="bi bi-heart-fill"></i></div><h3>Values-Based</h3><p>Founded on Adventist principles of integrity and excellence.</p></div>
        <div class="feature-card"><div class="feature-icon"><i class="bi bi-globe2"></i></div><h3>Global Standards</h3><p>Internationally recognized certifications and partnerships.</p></div>
      </div>
    </div>
  </section>

  <!-- Gallery Teaser -->
  <div style="text-align:center;padding:40px 20px;background:var(--navy);">
    <p style="color:rgba(255,255,255,.6);font-size:.9rem;margin-bottom:12px;">Explore campus life through our photo collection</p>
    <a href="#gallery" data-page="gallery" class="btn btn-primary">View Our Gallery <i class="bi bi-images"></i></a>
  </div>
</section>

<!-- ===== ABOUT PAGE ===== -->
<section id="about" class="page">
  <div class="page-banner"><div class="container"><div class="breadcrumb"><a href="#home" data-page="home">Home</a><i class="bi bi-chevron-right" style="font-size:.7rem;"></i>About</div><h1>About KATSS</h1><p>Committed to holistic education integrating faith, learning, and service</p></div></div>

  <section class="section">
    <div class="container">
      <p class="section-label">Our Story</p><h2 class="section-title">History & Vision</h2><div class="divider"></div>
      <p style="font-size:1rem;color:var(--text-muted);line-height:1.8;max-width:740px;">Founded in 2004, KATSS was established to address the critical need for skilled technical professionals in the Kirehe region. Located in Kigina Sector, 131 KM from Kigali on the Kigali–Rusumo Road, our school has grown into a leading TVET institution in Eastern Rwanda.</p>
    </div>
  </section>

  <section class="section section--alt">
    <div class="container">
      <div class="text-center"><p class="section-label">Our Foundation</p><h2 class="section-title">Mission, Vision & Values</h2><div class="divider"></div></div>
      <div class="mvv-grid">
        <div class="mvv-card featured"><i class="bi bi-bullseye"></i><h3>Mission</h3><p>To empower students with career-ready technical skills and instill Christian principles.</p></div>
        <div class="mvv-card"><i class="bi bi-eye"></i><h3>Vision</h3><p>To produce globally competitive, innovative, and ethically grounded technical professionals.</p></div>
        <div class="mvv-card"><i class="bi bi-star"></i><h3>Core Values</h3><ul style="text-align:left;padding-left:16px;"><li>✦ Integrity</li><li>✦ Excellence</li><li>✦ Service</li><li>✦ Innovation</li></ul></div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <p class="section-label">Leadership</p><h2 class="section-title">A Message from the Headmaster</h2><div class="divider"></div>
      <blockquote style="border-left:4px solid var(--gold);padding:16px 24px;margin:0 0 32px;font-style:italic;color:var(--text-muted);font-size:1.05rem;line-height:1.8;background:var(--cream);border-radius:0 var(--radius-sm) var(--radius-sm) 0;">"At KATSS, we believe in unlocking the potential of every student. Our state-of-the-art workshops and dedicated staff provide an environment where hands-on learning thrives."</blockquote>
      <h3 style="font-family:var(--font-display);font-size:1.2rem;color:var(--navy);margin-bottom:20px;">Management Team</h3>
      <div class="leadership-grid">
        <div class="leader-card"><div class="role">Director of Study</div><div class="contact-info">Contact: 078xxxxxxxx</div></div>
        <div class="leader-card"><div class="role">Discipline Master</div><div class="contact-info">Contact: 0788853705</div></div>
        <div class="leader-card"><div class="role">Accountant</div><div class="contact-info">Contact: 07899999999</div></div>
      </div>
    </div>
  </section>
</section>

<!-- ===== ACADEMICS PAGE ===== -->
<section id="academics" class="page">
  <div class="page-banner"><div class="container"><div class="breadcrumb"><a href="#home" data-page="home">Home</a><i class="bi bi-chevron-right" style="font-size:.7rem;"></i>Academics</div><h1>Academic Programs</h1><p>Nurturing the future of technology and leadership in Rwanda</p></div></div>

  <section class="section">
    <div class="container">
      <p class="section-label">Academic Pathways</p><h2 class="section-title">Our Three Learning Tracks</h2><div class="divider"></div>
      <div class="levels-grid">
        <div class="level-card"><div class="aspect aspect--3-2"><img src="../images/images.jpeg" alt="O-Level" loading="lazy" onerror="this.src='https://placehold.co/600x400/002147/D4AF37?text=O+Level';"></div><div class="level-details"><h3>Ordinary Level (S1–S3)</h3><p>Establishes a strong foundation in core sciences, humanities, and languages.</p></div></div>
        <div class="level-card"><div class="aspect aspect--3-2"><img src="../images/1000003811.jpg" alt="A-Level" loading="lazy" onerror="this.src='https://placehold.co/600x400/002147/D4AF37?text=A+Level';"></div><div class="level-details"><h3>Advanced Level (S4–S6)</h3><p>Students select professional combinations to prepare for university entrance.</p></div></div>
        <div class="level-card"><div class="aspect aspect--3-2"><img src="../images/auto.png" alt="TVET" loading="lazy" onerror="this.src='https://placehold.co/600x400/002147/D4AF37?text=TVET';"></div><div class="level-details"><h3>TVET Programs</h3><p>Dedicated technical vocational training leading to industry certifications.</p></div></div>
      </div>
    </div>
  </section>

  <section class="section section--alt">
    <div class="container">
      <div class="text-center"><p class="section-label">TVET Offerings</p><h2 class="section-title">Specialized Technical Programs</h2><div class="divider"></div></div>
      <div class="info-grid">
        <div class="info-box"><i class="bi bi-code-slash"></i><h3>Software Development</h3><p>Programming, web design, mobile app development, and database management.</p></div>
        <div class="info-box"><i class="bi bi-calculator"></i><h3>Accounting</h3><p>Financial record-keeping, bookkeeping, tax compliance, and commercial practices.</p></div>
        <div class="info-box"><i class="bi bi-camera-video"></i><h3>Multimedia</h3><p>Graphic design, video editing, photography, and audio production.</p></div>
        <div class="info-box"><i class="bi bi-bricks"></i><h3>Building Construction</h3><p>Masonry, carpentry, site supervision, and sustainable building techniques.</p></div>
        <div class="info-box"><i class="bi bi-geo-alt"></i><h3>Tourism</h3><p>Hospitality management, tour guiding, and promoting Rwanda's cultural heritage.</p></div>
        <div class="info-box"><i class="bi bi-truck"></i><h3>Automobile Technology</h3><p>Engine repair, electronic diagnostics, and modern automotive systems.</p></div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="text-center"><p class="section-label">Our Approach</p><h2 class="section-title">Curriculum Focus & Values</h2><div class="divider"></div></div>
      <div class="curriculum-grid">
        <div class="curriculum-box"><h3>Academic Excellence</h3><ul><li><i class="bi bi-check-circle-fill"></i>High student-teacher ratio</li><li><i class="bi bi-check-circle-fill"></i>Strong STEM emphasis</li><li><i class="bi bi-check-circle-fill"></i>Regular assessments</li></ul></div>
        <div class="curriculum-box featured"><h3>Faith & Character</h3><ul><li><i class="bi bi-patch-check-fill"></i>Christian-based values</li><li><i class="bi bi-patch-check-fill"></i>Integrity and ethics</li><li><i class="bi bi-patch-check-fill"></i>Holistic development</li></ul></div>
        <div class="curriculum-box"><h3>Practical Skills</h3><ul><li><i class="bi bi-check-circle-fill"></i>Workshop sessions</li><li><i class="bi bi-check-circle-fill"></i>Industrial internship</li><li><i class="bi bi-check-circle-fill"></i>Entrepreneurship training</li></ul></div>
      </div>
    </div>
  </section>
</section>

<!-- ===== NEWS & EVENTS PAGE ===== -->
<section id="news-events" class="page">
  <div class="page-banner"><div class="container"><div class="breadcrumb"><a href="#home" data-page="home">Home</a><i class="bi bi-chevron-right" style="font-size:.7rem;"></i>News & Events</div><h1>News & Events</h1><p>Stay up to date with the latest happenings at KATSS</p></div></div>
  <section class="section">
    <div class="container">
      <div class="news-grid">
        <?php if (!empty($events)): foreach ($events as $event): ?>
        <article class="card">
          <div class="aspect aspect--16-9">
            <img src="<?php echo event_image($event); ?>" 
                 alt="<?php echo htmlspecialchars($event['title']); ?>" 
                 loading="lazy"
                 onerror="this.onerror=null;this.src='https://placehold.co/600x338/002147/D4AF37?text=News';">
          </div>
          <div class="card-body">
            <span class="card-label"><?php echo htmlspecialchars($event['category'] ?? 'General'); ?></span>
            <h3 class="card-title"><a href="#"><?php echo htmlspecialchars($event['title']); ?></a></h3>
            <p class="card-excerpt"><?php echo htmlspecialchars(mb_substr($event['excerpt'] ?? $event['content'], 0, 150)); ?>…</p>
            <div class="card-meta"><i class="bi bi-calendar3"></i><time><?php echo date('F d, Y', strtotime($event['event_date'])); ?></time></div>
          </div>
        </article>
        <?php endforeach; else: ?>
        <article class="card"><div class="aspect aspect--16-9"><img src="../images/sport.jpg" alt="News" loading="lazy" onerror="this.src='https://placehold.co/600x338/002147/D4AF37?text=News';"></div><div class="card-body"><span class="card-label">Academics</span><h3 class="card-title"><a href="#">Record-Breaking National Exam Results</a></h3><p class="card-excerpt">KATSS celebrates its highest-ever pass rate in national examinations.</p><div class="card-meta"><i class="bi bi-calendar3"></i><time>October 7, 2025</time></div></div></article>
        <article class="card"><div class="aspect aspect--16-9"><img src="../images/schoolcomp.jpg" alt="Service" loading="lazy" onerror="this.src='https://placehold.co/600x338/002147/D4AF37?text=Service';"></div><div class="card-body"><span class="card-label">Service</span><h3 class="card-title"><a href="#">Community Clean-Up Drive</a></h3><p class="card-excerpt">Students volunteered to renovate the local Kirehe public library.</p><div class="card-meta"><i class="bi bi-calendar3"></i><time>September 15, 2025</time></div></div></article>
        <article class="card"><div class="aspect aspect--16-9"><img src="../images/1000004436.jpg" alt="Teachers Day" loading="lazy" onerror="this.src='https://placehold.co/600x338/002147/D4AF37?text=Teachers+Day';"></div><div class="card-body"><span class="card-label">Staff</span><h3 class="card-title"><a href="#">World Teachers' Day Celebration</a></h3><p class="card-excerpt">KATSS joined Rwanda in honouring outstanding educators.</p><div class="card-meta"><i class="bi bi-calendar3"></i><time>November 02, 2024</time></div></div></article>
        <?php endif; ?>
      </div>
    </div>
  </section>
</section>

<!-- ===== STUDENT LIFE PAGE ===== -->
<section id="student-life" class="page">
  <div class="page-banner"><div class="container"><div class="breadcrumb"><a href="#home" data-page="home">Home</a><i class="bi bi-chevron-right" style="font-size:.7rem;"></i>Student Life</div><h1>Student Experience</h1><p>A vibrant blend of technical study, spiritual growth, and extracurricular activity</p></div></div>

  <section class="section">
    <div class="container">
      <p class="section-label">Beyond the Classroom</p><h2 class="section-title">Clubs & Activities</h2><div class="divider"></div>
      <div class="info-grid">
        <div class="info-box"><h4>Technical & Innovation</h4><ul><li>Robotics & Coding Club</li><li>Construction Design Society</li><li>Digital Marketing Team</li></ul></div>
        <div class="info-box"><h4>Sports & Arts</h4><ul><li>Football, Basketball & Volleyball</li><li>Drama & Choir</li><li>Adventist Youth (AY) Society</li></ul></div>
      </div>
    </div>
  </section>

  <section class="section section--alt">
    <div class="container">
      <p class="section-label">Faith & Wellbeing</p><h2 class="section-title">Spiritual Life & Facilities</h2><div class="divider"></div>
      <div class="info-grid" style="margin-bottom:40px;">
        <div class="info-box"><h4>Daily Worship</h4><p>Morning and evening worship conducted in school and boarding facilities.</p></div>
        <div class="info-box"><h4>Chaplaincy Support</h4><p>The Chaplaincy provides counseling, guidance, and spiritual growth programs.</p></div>
      </div>
      <div class="facilities-grid">
        <div class="facility-card"><div class="facility-icon"><i class="bi bi-window"></i></div><h3>Computer Labs</h3><p>Fully equipped with latest software and hardware.</p></div>
        <div class="facility-card"><div class="facility-icon"><i class="bi bi-tools"></i></div><h3>Workshop Facilities</h3><p>Professional-grade tools for construction and auto.</p></div>
        <div class="facility-card"><div class="facility-icon"><i class="bi bi-book"></i></div><h3>Digital Library</h3><p>Access to thousands of e-books and research journals.</p></div>
        <div class="facility-card"><div class="facility-icon"><i class="bi bi-house-door"></i></div><h3>Hostel Facilities</h3><p>Comfortable, secure accommodation with 24/7 support.</p></div>
        <div class="facility-card"><div class="facility-icon"><i class="bi bi-heart-pulse"></i></div><h3>Medical Centre</h3><p>On-campus health clinic providing basic medical care.</p></div>
        <div class="facility-card"><div class="facility-icon"><i class="bi bi-cup-hot"></i></div><h3>Cafeteria</h3><p>Modern dining facilities serving nutritious meals.</p></div>
      </div>
    </div>
  </section>
</section>

<!-- ===== GALLERY PAGE ===== -->
<section id="gallery" class="page">
  <div class="page-banner"><div class="container"><div class="breadcrumb"><a href="#home" data-page="home">Home</a><i class="bi bi-chevron-right" style="font-size:.7rem;"></i>Gallery</div><h1>Photo & Video Gallery</h1><p>Discover life at KATSS through our campus collection</p></div></div>
  <section class="section">
    <div class="container">
      <div class="gallery-grid">
        <?php if (!empty($gallery_items)): foreach ($gallery_items as $item):
          $mediaType = $item['media_type'] ?? 'image';
          if ($mediaType === 'video'): ?>
          <div class="gallery-item gallery-item--video" data-type="video" data-video="<?php echo gallery_video($item); ?>">
            <div class="video-thumb">
              <video muted loop preload="metadata" poster="<?php echo gallery_image($item); ?>">
                <source src="<?php echo gallery_video($item); ?>" type="video/mp4">
              </video>
              <div class="play-overlay"><i class="bi bi-play-circle-fill"></i></div>
            </div>
            <div class="gallery-overlay"><h4><?php echo htmlspecialchars($item['title']); ?></h4></div>
          </div>
          <?php else: ?>
          <div class="gallery-item" data-type="image" data-src="<?php echo gallery_image($item); ?>">
            <div class="aspect aspect--3-2">
              <img src="<?php echo gallery_image($item); ?>" 
                   alt="<?php echo htmlspecialchars($item['title']); ?>" 
                   loading="lazy"
                   onerror="this.onerror=null;this.src='https://placehold.co/600x400/002147/D4AF37?text=<?php echo urlencode($item['title'] ?? 'Gallery'); ?>';">
            </div>
            <div class="gallery-overlay"><h4><?php echo htmlspecialchars($item['title']); ?></h4></div>
          </div>
          <?php endif;
        endforeach; else: ?>
        <?php $static = [
          ['src'=>'../images/1000003811.jpg','title'=>'Classroom Learning'],
          ['src'=>'../images/lab.jpg','title'=>'Computer Laboratory'],
          ['src'=>'../images/sport.jpg','title'=>'Sports Day'],
          ['src'=>'../images/account.jpg','title'=>'Accounting Program'],
          ['src'=>'../images/bdc.jpg','title'=>'Technical Workshop'],
          ['src'=>'../images/1000005151.jpg','title'=>'School Compound']
        ];
        foreach ($static as $g): ?>
        <div class="gallery-item" data-type="image" data-src="<?php echo $g['src']; ?>">
          <div class="aspect aspect--3-2">
            <img src="<?php echo $g['src']; ?>" alt="<?php echo $g['title']; ?>" loading="lazy" onerror="this.src='https://placehold.co/600x400/002147/D4AF37?text=<?php echo urlencode($g['title']); ?>';">
          </div>
          <div class="gallery-overlay"><h4><?php echo $g['title']; ?></h4></div>
        </div>
        <?php endforeach; ?>
        <div class="gallery-item gallery-item--video" data-type="video" data-video="../images/tors.mp4">
          <div class="video-thumb"><video muted loop preload="metadata"><source src="../images/tors.mp4" type="video/mp4"></video><div class="play-overlay"><i class="bi bi-play-circle-fill"></i></div></div>
          <div class="gallery-overlay"><h4>Campus Tour 2024</h4></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</section>

<!-- ===== ADMISSIONS PAGE ===== -->
<section id="admissions" class="page">
  <div class="page-banner"><div class="container"><div class="breadcrumb"><a href="#home" data-page="home">Home</a><i class="bi bi-chevron-right" style="font-size:.7rem;"></i>Admissions</div><h1>Admissions</h1><p>Join a community committed to technical excellence and moral values</p></div></div>
  <section class="section">
    <div class="container">
      <div class="admissions-section"><h3>1. Overview</h3><p style="color:var(--text-muted);font-size:.92rem;line-height:1.75;margin-bottom:16px;">KATSS welcomes prospective students who are driven, ambitious, and ready to embrace a hands-on learning environment.</p><a href="#contact" data-page="contact" class="btn btn-navy btn-sm">Contact Admissions Office <i class="bi bi-chat-text"></i></a></div>
      <div class="admissions-section"><h3>2. Available Programs</h3><ul class="program-list"><li><i class="bi bi-cpu"></i> Software Development</li><li><i class="bi bi-tools"></i> Building Construction</li><li><i class="bi bi-book-fill"></i> Accounting</li><li><i class="bi bi-gear"></i> Automobile Technology</li><li><i class="bi bi-globe2"></i> Tourism</li><li><i class="bi bi-camera-video"></i> Multimedia Production</li></ul></div>
      <div class="admissions-section"><h3>3. Application Requirements</h3><ul class="requirements-list"><li>Primary School Certificate (P6 or equivalent)</li><li>Junior Secondary Certificate (S3 or equivalent)</li><li>Academic Transcripts from last two years</li><li>Two recent passport-size photographs</li><li>Copy of National ID, Passport, or Birth Certificate</li><li>Application Fee Payment Slip</li><li>Pass the mandatory school entrance examination</li></ul></div>
    </div>
  </section>
</section>

<!-- ===== CONTACT PAGE ===== -->
<section id="contact" class="page">
  <div class="page-banner"><div class="container"><div class="breadcrumb"><a href="#home" data-page="home">Home</a><i class="bi bi-chevron-right" style="font-size:.7rem;"></i>Contact</div><h1>Contact Us</h1><p>Get in touch — we are here to help</p></div></div>
  <section class="section">
    <div class="container">
      <div class="contact-layout">
        <div>
          <p class="section-label">Get In Touch</p><h2 class="section-title">Send Us a Message</h2><div class="divider"></div>
          <form id="contactForm">
            <div class="form-group"><input type="text" name="from_name" placeholder="Your Full Name" required></div>
            <div class="form-group"><input type="email" name="from_email" placeholder="Your Email Address" required></div>
            <div class="form-group"><input type="tel" name="phone" placeholder="Phone Number (Optional)"></div>
            <div class="form-group"><textarea name="message" rows="5" placeholder="Your Message or Inquiry…" required></textarea></div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-send-fill"></i> Send Message</button>
            <p id="form-status"></p>
          </form>
        </div>
        <div>
          <p class="section-label">Our Location</p><h2 class="section-title">Find Us</h2><div class="divider"></div>
          <div class="info-box" style="margin-bottom:24px;">
            <p style="display:flex;align-items:flex-start;gap:10px;margin-bottom:10px;font-size:.9rem;color:var(--text-muted);"><i class="bi bi-geo-alt-fill" style="color:var(--navy);"></i>Kigina Sector, Kirehe District, Eastern Province, Rwanda</p>
            <p style="display:flex;align-items:center;gap:10px;margin-bottom:10px;font-size:.9rem;color:var(--text-muted);"><i class="bi bi-envelope-fill" style="color:var(--navy);"></i>katsapapen@gmail.com</p>
            <p style="display:flex;align-items:center;gap:10px;font-size:.9rem;color:var(--text-muted);"><i class="bi bi-telephone-fill" style="color:var(--navy);"></i>+250 788 416 574</p>
          </div>
          <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15949.195325594458!2d30.709328249999997!3d-2.07172055!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x19d92419409825b1%3A0x6a13d39d672d68e2!2sKirehe%20District!5e0!3m2!1sen!2srw!4v1700000000000!5m2!1sen!2srw" width="100%" height="280" style="border:0;border-radius:var(--radius-md);" allowfullscreen="" loading="lazy" title="KATSS Location Map"></iframe>
        </div>
      </div>
    </div>
  </section>
</section>

</main>

<!-- FOOTER -->
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="footer-logo-wrap"><div class="footer-logo-img"><img src="../images/logo.jpeg" alt="KATSS" loading="lazy"></div><span class="footer-brand">KATSS</span></div>
        <p class="footer-desc">Kirehe Adventist Technical Secondary School — Empowering Students Through Technical Excellence and Adventist Values.</p>
        <p class="footer-motto"><em>Motto: Dream Believe Achieve</em></p>
        <div class="social-row">
          <a href="https://facebook.com" target="_blank" rel="noopener" class="social-btn" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
          <a href="https://youtube.com" target="_blank" rel="noopener" class="social-btn" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
          <a href="https://twitter.com" target="_blank" rel="noopener" class="social-btn" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
          <a href="https://instagram.com" target="_blank" rel="noopener" class="social-btn" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
        </div>
      </div>
      <div class="footer-col"><h4><i class="bi bi-book"></i> Programs</h4><ul><li><a href="#academics" data-page="academics"><i class="bi bi-chevron-right"></i> Software Development</a></li><li><a href="#academics" data-page="academics"><i class="bi bi-chevron-right"></i> Accounting</a></li><li><a href="#academics" data-page="academics"><i class="bi bi-chevron-right"></i> Building Construction</a></li><li><a href="#academics" data-page="academics"><i class="bi bi-chevron-right"></i> Multimedia</a></li><li><a href="#academics" data-page="academics"><i class="bi bi-chevron-right"></i> Automobile Technology</a></li><li><a href="#academics" data-page="academics"><i class="bi bi-chevron-right"></i> Tourism</a></li></ul></div>
      <div class="footer-col"><h4><i class="bi bi-link-45deg"></i> Quick Links</h4><ul><li><a href="#home" data-page="home"><i class="bi bi-chevron-right"></i> Home</a></li><li><a href="#about" data-page="about"><i class="bi bi-chevron-right"></i> About Us</a></li><li><a href="#student-life" data-page="student-life"><i class="bi bi-chevron-right"></i> Student Life</a></li><li><a href="#admissions" data-page="admissions"><i class="bi bi-chevron-right"></i> Admissions</a></li><li><a href="#gallery" data-page="gallery"><i class="bi bi-chevron-right"></i> Gallery</a></li><li><a href="#contact" data-page="contact"><i class="bi bi-chevron-right"></i> Contact</a></li></ul></div>
      <div class="footer-col footer-contact">
        <h4><i class="bi bi-telephone-fill"></i> Contact</h4>
        <p><i class="bi bi-geo-alt-fill"></i>Kigina Sector, Kirehe District, Eastern Rwanda</p>
        <p><i class="bi bi-telephone-fill"></i>+250 788 416 574</p>
        <p><i class="bi bi-envelope-fill"></i>katsapapen@gmail.com</p>
        <div style="margin-top:20px;">
          <p style="font-size:.82rem;color:rgba(255,255,255,.45);margin-bottom:8px;">Subscribe to our newsletter</p>
          <form id="newsletter-form" class="newsletter-form"><input type="email" id="email-input" placeholder="Your Email" required><button type="submit">Subscribe</button></form>
          <p id="subscription-message"></p>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> Kirehe Adventist Technical Secondary School. All rights reserved.</p>
      <div class="footer-links"><a href="#privacy">Privacy Policy</a><a href="#terms">Terms of Use</a><a href="#sitemap">Sitemap</a></div>
    </div>
  </div>
</footer>

<!-- FLOATING BUTTONS -->
<a href="https://wa.me/250788416574" target="_blank" rel="noopener" class="whatsapp-btn" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
<button id="backToTop" class="back-to-top" aria-label="Back to top"><i class="bi bi-arrow-up"></i></button>

<!-- LIGHTBOX -->
<div id="lightbox" class="lightbox" role="dialog" aria-modal="true">
  <button class="lightbox-close" id="lightboxClose">&times;</button>
  <div class="lightbox-nav"><button id="lightboxPrev"><i class="bi bi-chevron-left"></i></button><button id="lightboxNext"><i class="bi bi-chevron-right"></i></button></div>
  <div id="lightbox-img-wrap"><img id="lightbox-img" src="" alt="" loading="lazy"></div>
  <div id="lightbox-video" style="display:none;"><video id="video-player" controls style="max-width:90vw;max-height:80vh;border-radius:8px;"></video></div>
</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
<script src="script.js"></script>
</body>
</html>
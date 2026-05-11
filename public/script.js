/**
 * KATSS - Main JavaScript
 * Handles: SPA navigation, header scroll, mobile menu, lightbox, sliders, forms
 */
(function () {
  'use strict';

  /* ── EmailJS Initialization ── */
  (function() {
    if (typeof emailjs !== 'undefined') {
      emailjs.init("vNc8MXvN5Xl0NLVsy");
    }
  })();

  /* ── DOM Elements ── */
  const header      = document.getElementById('siteHeader');
  const hamburger   = document.getElementById('hamburger');
  const mobileNav   = document.getElementById('mobileNav');
  const mobileClose = document.getElementById('mobileClose');
  const navOverlay  = document.getElementById('navOverlay');
  const backToTop   = document.getElementById('backToTop');
  const pages       = document.querySelectorAll('.page');
  const navLinks    = document.querySelectorAll('[data-page]');

  /* ── Header Scroll Behavior ── */
  function handleScroll() {
    const scrolled = window.scrollY > 40;
    header.classList.toggle('scrolled', scrolled);
    header.classList.toggle('transparent', !scrolled);
    
    // Back to top button
    backToTop.classList.toggle('show', window.scrollY > 400);
  }

  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll();

  /* ── Mobile Navigation ── */
  function openMobileNav() {
    mobileNav.classList.add('open');
    navOverlay.classList.add('show');
    hamburger.classList.add('open');
    hamburger.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }

  function closeMobileNav() {
    mobileNav.classList.remove('open');
    navOverlay.classList.remove('show');
    hamburger.classList.remove('open');
    hamburger.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  hamburger.addEventListener('click', function () {
    mobileNav.classList.contains('open') ? closeMobileNav() : openMobileNav();
  });

  mobileClose.addEventListener('click', closeMobileNav);
  navOverlay.addEventListener('click', closeMobileNav);

  // Close mobile nav on Escape
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && mobileNav.classList.contains('open')) {
      closeMobileNav();
    }
  });

  /* ── SPA Navigation ── */
  function showPage(pageId) {
    // Hide all pages
    pages.forEach(function (p) {
      p.classList.remove('active');
    });

    // Show target page
    const target = document.getElementById(pageId);
    if (target) {
      target.classList.add('active');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Update active nav links
    navLinks.forEach(function (a) {
      const isActive = a.getAttribute('data-page') === pageId;
      a.classList.toggle('active', isActive);
    });

    // Header styling
    if (pageId === 'home') {
      header.classList.add('transparent');
      handleScroll();
    } else {
      header.classList.remove('transparent');
      header.classList.add('scrolled');
    }

    // Close mobile nav
    closeMobileNav();
  }

  // Attach click handlers to all navigation links
  navLinks.forEach(function (a) {
    a.addEventListener('click', function (e) {
      e.preventDefault();
      const pageId = a.getAttribute('data-page');
      if (pageId) {
        showPage(pageId);
      }
    });
  });

  // Handle hash in URL on page load
  function handleInitialHash() {
    const hash = window.location.hash.replace('#', '');
    if (hash && document.getElementById(hash)) {
      showPage(hash);
    }
  }

  handleInitialHash();
  window.addEventListener('hashchange', handleInitialHash);

  /* ── Back to Top ── */
  backToTop.addEventListener('click', function (e) {
    e.preventDefault();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  /* ── Swiper Initializations ── */
  function initSwipers() {
    // Highlights Swiper
    const highlightsEl = document.querySelector('.highlights-swiper');
    if (highlightsEl) {
      new Swiper('.highlights-swiper', {
        loop: true,
        autoplay: { delay: 6000, disableOnInteraction: false },
        pagination: {
          el: '.highlights-swiper .swiper-pagination',
          clickable: true
        },
        effect: 'fade',
        fadeEffect: { crossFade: true }
      });
    }

    // Pathways/Programs Swiper
    const pathwaysNext = document.querySelector('.pathways-next');
    const pathwaysPrev = document.querySelector('.pathways-prev');
    const pathwaysPagination = document.querySelector('.pathways-pagination');
    
    if (pathwaysNext || pathwaysPrev) {
      new Swiper('.pathways-swiper', {
        loop: true,
        slidesPerView: 1,
        spaceBetween: 24,
        autoplay: { delay: 4500, disableOnInteraction: false },
        navigation: {
          nextEl: '.pathways-next',
          prevEl: '.pathways-prev'
        },
        pagination: {
          el: '.pathways-pagination',
          clickable: true
        },
        breakpoints: {
          540: { slidesPerView: 2 },
          900: { slidesPerView: 3 }
        }
      });
    }

    // Testimonials Swiper
    const testimonialsEl = document.querySelector('.testimonials-swiper');
    if (testimonialsEl) {
      new Swiper('.testimonials-swiper', {
        loop: true,
        autoplay: { delay: 5500, disableOnInteraction: false },
        pagination: {
          el: '.testimonials-swiper .swiper-pagination',
          clickable: true
        },
        slidesPerView: 1,
        spaceBetween: 24,
        breakpoints: {
          768: { slidesPerView: 2 },
          1024: { slidesPerView: 3 }
        }
      });
    }
  }

  initSwipers();

  /* ── Gallery Lightbox ── */
  const galleryItems  = document.querySelectorAll('.gallery-item');
  const lightbox      = document.getElementById('lightbox');
  const lbImg         = document.getElementById('lightbox-img');
  const lbImgWrap     = document.getElementById('lightbox-img-wrap');
  const lbVideoWrap   = document.getElementById('lightbox-video');
  const lbVideo       = document.getElementById('video-player');
  const lbClose       = document.getElementById('lightboxClose');
  const lbPrev        = document.getElementById('lightboxPrev');
  const lbNext        = document.getElementById('lightboxNext');

  let currentIndex = 0;
  const imageItems = Array.from(galleryItems).filter(function (el) {
    return el.getAttribute('data-type') === 'image';
  });

  function openLightbox(item) {
    const type = item.getAttribute('data-type');
    
    if (type === 'video') {
      lbImgWrap.style.display = 'none';
      lbVideoWrap.style.display = 'block';
      lbVideo.src = item.getAttribute('data-video') || '';
      lbPrev.style.display = 'none';
      lbNext.style.display = 'none';
    } else {
      lbImgWrap.style.display = 'block';
      lbVideoWrap.style.display = 'none';
      lbVideo.pause();
      lbVideo.src = '';
      
      lbImg.src = item.getAttribute('data-src') || '';
      const title = item.querySelector('h4');
      lbImg.alt = title ? title.textContent : '';
      
      lbPrev.style.display = '';
      lbNext.style.display = '';
      currentIndex = imageItems.indexOf(item);
    }
    
    lightbox.classList.add('open');
    document.body.style.overflow = 'hidden';
    
    // Trap focus
    lbClose.focus();
  }

  function closeLightbox() {
    lightbox.classList.remove('open');
    lbVideo.pause();
    lbVideo.src = '';
    document.body.style.overflow = '';
  }

  // Attach click events to gallery items
  galleryItems.forEach(function (item) {
    item.addEventListener('click', function () {
      openLightbox(item);
    });
    
    // Keyboard accessibility
    item.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        openLightbox(item);
      }
    });
    
    // Make gallery items focusable
    item.setAttribute('tabindex', '0');
    item.setAttribute('role', 'button');
  });

  lbClose.addEventListener('click', closeLightbox);

  // Close on overlay click
  lightbox.addEventListener('click', function (e) {
    if (e.target === lightbox) {
      closeLightbox();
    }
  });

  // Previous/Next navigation
  lbPrev.addEventListener('click', function () {
    if (imageItems.length === 0) return;
    currentIndex = (currentIndex - 1 + imageItems.length) % imageItems.length;
    openLightbox(imageItems[currentIndex]);
  });

  lbNext.addEventListener('click', function () {
    if (imageItems.length === 0) return;
    currentIndex = (currentIndex + 1) % imageItems.length;
    openLightbox(imageItems[currentIndex]);
  });

  // Keyboard navigation for lightbox
  document.addEventListener('keydown', function (e) {
    if (!lightbox.classList.contains('open')) return;
    
    switch (e.key) {
      case 'Escape':
        closeLightbox();
        break;
      case 'ArrowLeft':
        lbPrev.click();
        break;
      case 'ArrowRight':
        lbNext.click();
        break;
    }
  });

  /* ── Newsletter Form ── */
  const newsletterForm = document.getElementById('newsletter-form');
  if (newsletterForm) {
    newsletterForm.addEventListener('submit', function (e) {
      e.preventDefault();
      
      const emailInput = document.getElementById('email-input');
      const msg = document.getElementById('subscription-message');
      
      if (!emailInput || !msg) return;
      
      const email = emailInput.value.trim();
      if (!email) {
        msg.textContent = 'Please enter your email address.';
        msg.style.color = '#f87171';
        return;
      }
      
      msg.textContent = 'Subscribing...';
      msg.style.color = 'rgba(255,255,255,.6)';
      
      if (typeof emailjs !== 'undefined') {
        emailjs.send('service_r4cj7xg', 'template_mn5geej', {
          user_email: email
        })
        .then(function () {
          msg.textContent = '✓ Subscribed successfully!';
          msg.style.color = '#4ade80';
          newsletterForm.reset();
        })
        .catch(function (err) {
          msg.textContent = '✗ Failed. Please try again later.';
          msg.style.color = '#f87171';
          console.error('Newsletter Error:', err);
        });
      } else {
        msg.textContent = '✓ Thank you for subscribing!';
        msg.style.color = '#4ade80';
        newsletterForm.reset();
      }
    });
  }

  /* ── Contact Form ── */
  const contactForm = document.getElementById('contactForm');
  if (contactForm) {
    contactForm.addEventListener('submit', function (e) {
      e.preventDefault();
      
      const status = document.getElementById('form-status');
      if (!status) return;
      
      status.textContent = 'Sending message...';
      status.style.color = 'var(--text-muted)';
      
      if (typeof emailjs !== 'undefined') {
        emailjs.sendForm('service_r4cj7xg', 'template_contact_form', this)
          .then(function () {
            status.textContent = '✓ Message sent successfully! We\'ll get back to you soon.';
            status.style.color = '#16a34a';
            contactForm.reset();
          })
          .catch(function (err) {
            status.textContent = '✗ Failed to send. Please email us directly at katsapapen@gmail.com';
            status.style.color = '#dc2626';
            console.error('Contact Form Error:', err);
          });
      } else {
        // Fallback if EmailJS isn't loaded
        status.textContent = 'Please email us directly at katsapapen@gmail.com';
        status.style.color = '#f59e0b';
      }
    });
  }

  /* ── Scroll Animation (fade-up elements) ── */
  function handleScrollAnimations() {
    const fadeElements = document.querySelectorAll('.fade-up');
    
    if (!('IntersectionObserver' in window)) return;
    
    const observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.style.animationPlayState = 'running';
          observer.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    });
    
    fadeElements.forEach(function (el) {
      observer.observe(el);
    });
  }
  
  handleScrollAnimations();

  /* ── Smooth scroll for anchor links (non-SPA) ── */
  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (e) {
      const href = this.getAttribute('href');
      // Skip if handled by SPA navigation
      if (this.hasAttribute('data-page')) return;
      
      if (href && href !== '#') {
        e.preventDefault();
        const target = document.querySelector(href);
        if (target) {
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
  });

  /* ── Announcement Button Scroll ── */
  window.scrollToAnnouncements = function(e) {
    if (e) e.preventDefault();
    
    const announcementsSection = document.getElementById('news-events');
    if (announcementsSection) {
      announcementsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  };

  /* ── Announcement Icon Tooltip ── */
  document.addEventListener('DOMContentLoaded', function() {
    const announcementIcon = document.querySelector('.announcement-icon');
    const announcementTooltip = document.getElementById('announcementTooltip');
    
    if (announcementIcon && announcementTooltip) {
      // Show tooltip on hover
      announcementIcon.addEventListener('mouseenter', function() {
        announcementTooltip.style.visibility = 'visible';
        announcementTooltip.style.opacity = '1';
      });
      
      // Hide tooltip on mouse leave with delay
      announcementIcon.addEventListener('mouseleave', function() {
        setTimeout(function() {
          announcementTooltip.style.visibility = 'hidden';
          announcementTooltip.style.opacity = '0';
        }, 200);
      });
      
      // Hide tooltip when clicking elsewhere
      document.addEventListener('click', function(e) {
        if (!announcementIcon.contains(e.target) && !announcementTooltip.contains(e.target)) {
          announcementTooltip.style.visibility = 'hidden';
          announcementTooltip.style.opacity = '0';
        }
      });
    }
  });

})();
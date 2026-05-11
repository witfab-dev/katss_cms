document.addEventListener('DOMContentLoaded', function() {

    // Global selectors for reusability
    const navLinks = document.querySelectorAll('.main-nav a');
    const pageSections = document.querySelectorAll('.page-content');
    const menuIcon = document.querySelector('.menu-icon');
    const navMenu = document.querySelector('.main-nav');
    const floatingCta = document.querySelector('.floating-cta-button');

    // Global variables to hold the Swiper instances
    let highlightSwiperInstance;
    let testimonialsSwiperInstance;
    let pathwaysSwiperInstance;

    // --- 1. Layout Recalculation Fix ---
    function forceLayoutRecalculation() {
        window.dispatchEvent(new Event('resize'));
        document.body.style.display='none';
        document.body.offsetHeight;
        document.body.style.display='';
    }

    // --- 2. Swiper Initialization and Update Function ---
    function initializeSwipers(forceUpdate = false) {
        
        // --- Highlights Carousel ---
        const highlightSwiperElement = document.querySelector('.highlights-swiper');
        if (highlightSwiperElement) {
            if (highlightSwiperInstance) {
                if (forceUpdate) highlightSwiperInstance.update();
            } else {
                highlightSwiperInstance = new Swiper(highlightSwiperElement, {
                    loop: true,
                    spaceBetween: 0,
                    slidesPerView: 1,
                    effect: 'fade',
                    direction: 'horizontal',
                    fadeEffect: { crossFade: true },
                    autoplay: {
                        delay: 15000,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true,
                    },
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true,
                    },
                });
            }
        }

        // --- Testimonials Swiper ---
        const testimonialsSwiperElement = document.querySelector('.testimonials-swiper');
        if (testimonialsSwiperElement) {
            if (testimonialsSwiperInstance) {
                if (forceUpdate) testimonialsSwiperInstance.update();
            } else {
                testimonialsSwiperInstance = new Swiper(testimonialsSwiperElement, {
                    loop: true,
                    spaceBetween: 30,
                    slidesPerView: 1,
                    direction: 'vertical',
                    autoplay: {
                        delay: 5000,
                        disableOnInteraction: false,
                    },
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true,
                    },
                });
            }
        }

        // --- Pathways Swiper ---
        const pathwaysSwiperElement = document.querySelector('.pathways-swiper');
        if (pathwaysSwiperElement) {
            if (pathwaysSwiperInstance) {
                if (forceUpdate) pathwaysSwiperInstance.update();
            } else {
                pathwaysSwiperInstance = new Swiper(pathwaysSwiperElement, {
                    loop: true,
                    spaceBetween: 5,
                    slidesPerView: 1,
                    direction: 'horizontal',
                    autoplay: {
                        delay: 7000,
                        disableOnInteraction: false,
                    },
                    pagination: {
                        el: '.pathways-pagination',
                        clickable: true,
                    },
                    navigation: {
                        nextEl: '.pathways-next',
                        prevEl: '.pathways-prev',
                    },
                    breakpoints: {
                        600: { slidesPerView: 2, spaceBetween: 20, },
                        1024: { slidesPerView: 3, spaceBetween: 30, }
                    }
                });
            }
        }
    }

    // --- 3. Helper function for SPA page switching ---
    function activatePage(targetPageId, pushState = true) {
        pageSections.forEach(page => {
            page.style.display = 'none';
            page.classList.remove('active');
        });
        navLinks.forEach(navLink => {
            navLink.classList.remove('active');
        });
        const targetPage = document.getElementById(targetPageId);
        if (targetPage) {
            targetPage.style.display = 'block';
            targetPage.classList.add('active');
        } else {
            document.getElementById('home').style.display = 'block';
            document.getElementById('home').classList.add('active');
        }
        const activeLink = document.querySelector(`.main-nav a[data-page="${targetPageId}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
        if (pushState) {
            window.history.pushState(null, null, `#${targetPageId}`);
        }
        if (navMenu.classList.contains('open')) {
            navMenu.classList.remove('open');
        }
        
        // Update Swipers and recalculate layouts
        initializeSwipers(true);
        setTimeout(forceLayoutRecalculation, 100); 
    }

    // --- 4. Event Listeners for Navigation ---
    document.body.addEventListener('click', function(event) {
        let target = event.target;
        
        while (target != null && target.tagName !== 'A') {
            target = target.parentElement;
        }

        if (target && target.hasAttribute('data-page')) {
            event.preventDefault(); 
            const targetPageId = target.getAttribute('data-page');
            activatePage(targetPageId);
        }
    });
    
    // Handle back/forward browser navigation
    window.addEventListener('popstate', function() {
        const hash = window.location.hash.substring(1) || 'home';
        activatePage(hash, false);
    });

    // --- 5. Mobile Menu Toggle ---
    if (menuIcon && navMenu) {
        menuIcon.addEventListener('click', () => {
            navMenu.classList.toggle('open'); 
        });
    }

    // --- 6. Floating CTA Visibility ---
    window.addEventListener('scroll', function() {
        const admissionsLink = document.querySelector('.main-nav a[data-page="admissions"]');
        const admissionsActive = admissionsLink && admissionsLink.classList.contains('active');
        
        if (admissionsActive) {
            floatingCta.style.display = 'none';
        } else {
            floatingCta.style.display = 'flex';
        }
    });

    // --- 7. Handle initial page load based on URL hash ---
    function initializePage() {
        const hash = window.location.hash.substring(1) || 'home'; 
        activatePage(hash, false); 
    }

    // Call the initialization function on load
    initializePage();

    // Initialize gallery functionality
    initGalleryLightbox();
});

// ========================================
// COMPLETE LIGHTBOX JAVASCRIPT WITH PROPER ERROR HANDLING
// ========================================

// Global variables
let currentMediaIndex = 0;
let mediaItems = [];
let isVideoPlaying = false;
let isLightboxOpen = false;

// Initialize lightbox functionality
function initLightbox() {
    console.log('Initializing lightbox...');
    
    // Get all gallery items (images and videos)
    mediaItems = Array.from(document.querySelectorAll('.gallery-item'));
    
    if (mediaItems.length === 0) {
        console.log('No gallery items found');
        return;
    }
    
    console.log(`Found ${mediaItems.length} media items`);
    
    // Set up event listeners for lightbox elements
    setupLightboxEventListeners();
    
    // Initialize video controls (but don't show them yet)
    initVideoControls();
    
    console.log('Lightbox initialized successfully');
}

// Set up lightbox event listeners
function setupLightboxEventListeners() {
    const lightboxModal = document.getElementById('lightbox-modal');
    const lightboxClose = document.querySelector('.lightbox-close');
    const lightboxPrev = document.querySelector('.lightbox-prev');
    const lightboxNext = document.querySelector('.lightbox-next');
    
    // Close lightbox
    if (lightboxClose) {
        lightboxClose.addEventListener('click', closeLightbox);
    }
    
    // Navigation
    if (lightboxPrev) {
        lightboxPrev.addEventListener('click', showPreviousMedia);
    }
    
    if (lightboxNext) {
        lightboxNext.addEventListener('click', showNextMedia);
    }
    
    // Close on background click
    if (lightboxModal) {
        lightboxModal.addEventListener('click', (e) => {
            if (e.target === lightboxModal) {
                closeLightbox();
            }
        });
    }
    
    // Keyboard navigation
    document.addEventListener('keydown', handleKeyboardNavigation);
    
    // Gallery item clicks
    mediaItems.forEach((item, index) => {
        item.addEventListener('click', () => {
            currentMediaIndex = index;
            openLightbox();
        });
        
        // Keyboard accessibility
        item.setAttribute('tabindex', '0');
        item.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                currentMediaIndex = index;
                openLightbox();
            }
        });
    });
}

// Initialize video controls (hidden by default)
function initVideoControls() {
    const videoControls = document.querySelector('.video-controls');
    if (videoControls) {
        videoControls.style.display = 'none'; // Hide controls initially
    }
    
    const videoPlayer = document.getElementById('video-player');
    const playPauseBtn = document.querySelector('.play-pause');
    const muteBtn = document.querySelector('.mute-btn');
    const videoProgress = document.querySelector('.video-progress');
    const fullscreenBtn = document.querySelector('.fullscreen-btn');
    
    if (!videoPlayer) return;
    
    // Play/Pause button
    if (playPauseBtn) {
        playPauseBtn.addEventListener('click', togglePlayPause);
    }
    
    // Mute button
    if (muteBtn) {
        muteBtn.addEventListener('click', toggleMute);
    }
    
    // Progress bar
    if (videoProgress) {
        videoProgress.addEventListener('click', setVideoProgress);
    }
    
    // Fullscreen button
    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', toggleFullscreen);
    }
    
    // Video event listeners
    videoPlayer.addEventListener('timeupdate', updateVideoProgress);
    videoPlayer.addEventListener('loadedmetadata', handleVideoLoaded);
    videoPlayer.addEventListener('play', handleVideoPlay);
    videoPlayer.addEventListener('pause', handleVideoPause);
    videoPlayer.addEventListener('ended', handleVideoEnded);
    videoPlayer.addEventListener('volumechange', updateMuteButton);
    videoPlayer.addEventListener('error', handleVideoError);
    
    // Fullscreen change events
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
}

// Open lightbox
function openLightbox() {
    const lightboxModal = document.getElementById('lightbox-modal');
    const currentItem = mediaItems[currentMediaIndex];
    const isVideo = currentItem.classList.contains('video');
    
    if (!lightboxModal || !currentItem) return;
    
    console.log(`Opening lightbox for ${isVideo ? 'video' : 'image'} at index ${currentMediaIndex}`);
    
    // Reset lightbox state
    resetLightbox();
    
    // Show appropriate media type
    if (isVideo) {
        openVideoInLightbox(currentItem);
    } else {
        openImageInLightbox(currentItem);
    }
    
    // Show lightbox
    lightboxModal.classList.add('active');
    document.body.style.overflow = 'hidden';
    isLightboxOpen = true;
    
    // Focus for accessibility
    setTimeout(() => {
        const lightboxClose = document.querySelector('.lightbox-close');
        if (lightboxClose) lightboxClose.focus();
    }, 100);
    
    updateNavigationButtons();
}

// Reset lightbox state
function resetLightbox() {
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxVideo = document.getElementById('lightbox-video');
    const videoPlayer = document.getElementById('video-player');
    const videoControls = document.querySelector('.video-controls');
    const videoError = document.querySelector('.lightbox-video .video-error-message');
    
    // Hide all media
    if (lightboxImg) lightboxImg.style.display = 'none';
    if (lightboxVideo) lightboxVideo.style.display = 'none';
    
    // Hide video controls
    if (videoControls) {
        videoControls.style.display = 'none';
    }
    
    // Reset video
    if (videoPlayer) {
        videoPlayer.pause();
        videoPlayer.currentTime = 0;
        videoPlayer.innerHTML = '';
        videoPlayer.style.display = 'block'; // Show player by default
    }
    
    // Hide video error
    if (videoError) {
        videoError.style.display = 'none';
    }
    
    // Remove video mode
    const lightboxModal = document.getElementById('lightbox-modal');
    if (lightboxModal) {
        lightboxModal.classList.remove('video-mode');
    }
}

// Open image in lightbox
function openImageInLightbox(item) {
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxCaption = document.getElementById('lightbox-caption');
    const imgElement = item.querySelector('img');
    
    if (!lightboxImg || !imgElement) return;
    
    const imgSrc = imgElement.src;
    const caption = item.querySelector('.gallery-overlay h4')?.textContent || '';
    
    // Set image and caption
    lightboxImg.src = imgSrc;
    lightboxImg.alt = imgElement.alt;
    lightboxCaption.textContent = caption;
    lightboxImg.style.display = 'block';
    
    // Handle image loading
    lightboxImg.style.opacity = '0';
    lightboxImg.onload = () => {
        lightboxImg.style.opacity = '1';
    };
    
    // Handle image errors
    lightboxImg.onerror = () => {
        lightboxImg.src = 'https://placehold.co/800x600/003366/ffffff?text=Image+Not+Found';
        lightboxImg.alt = 'Image not available';
    };
}

// Open video in lightbox
function openVideoInLightbox(item) {
    const lightboxVideo = document.getElementById('lightbox-video');
    const lightboxCaption = document.getElementById('lightbox-caption');
    const videoPlayer = document.getElementById('video-player');
    const videoControls = document.querySelector('.video-controls');
    const videoError = document.querySelector('.lightbox-video .video-error-message');
    
    if (!lightboxVideo || !videoPlayer) return;
    
    const videoUrl = item.getAttribute('data-video');
    const caption = item.querySelector('.gallery-overlay h4')?.textContent || '';
    
    // Set video mode
    const lightboxModal = document.getElementById('lightbox-modal');
    if (lightboxModal) {
        lightboxModal.classList.add('video-mode');
    }
    
    // Set caption and show video container
    lightboxCaption.textContent = caption;
    lightboxVideo.style.display = 'block';
    
    // Configure video player
    videoPlayer.setAttribute('playsinline', '');
    videoPlayer.setAttribute('webkit-playsinline', '');
    videoPlayer.removeAttribute('muted');
    videoPlayer.controls = false; // We use custom controls
    
    // Set video source
    videoPlayer.innerHTML = `<source src="${videoUrl}" type="video/mp4">`;
    
    // Load video
    videoPlayer.load();
    
    // Check if video exists before trying to play
    checkVideoExists(videoUrl).then(exists => {
        if (exists) {
            console.log('Video exists, attempting to play');
            // Try to play automatically
            const playPromise = videoPlayer.play();
            
            if (playPromise !== undefined) {
                playPromise.then(() => {
                    console.log('Video autoplay successful');
                    isVideoPlaying = true;
                    updatePlayPauseButton(true);
                    // Show controls since video is working
                    if (videoControls) {
                        videoControls.style.display = 'flex';
                    }
                }).catch(error => {
                    console.log('Autoplay failed, waiting for user interaction:', error);
                    updatePlayPauseButton(false);
                    // Still show controls since video exists
                    if (videoControls) {
                        videoControls.style.display = 'flex';
                    }
                });
            }
        } else {
            console.log('Video file not found');
            handleVideoError();
        }
    }).catch(error => {
        console.log('Video check failed:', error);
        handleVideoError();
    });
}

// Check if video file exists
function checkVideoExists(url) {
    return new Promise((resolve) => {
        // For local development, we'll assume videos exist
        // In production, you might want to implement actual file checking
        if (url.includes('placeholder') || !url) {
            resolve(false);
        } else {
            // Simple fetch check (may not work for all servers due to CORS)
            fetch(url, { method: 'HEAD' })
                .then(response => resolve(response.ok))
                .catch(() => resolve(true)); // Assume exists if fetch fails (local files)
        }
    });
}

// Handle video loaded successfully
function handleVideoLoaded() {
    console.log('Video metadata loaded successfully');
    const videoControls = document.querySelector('.video-controls');
    if (videoControls) {
        videoControls.style.display = 'flex'; // Show controls when video loads
    }
    updateVideoDuration();
}

// Handle video play
function handleVideoPlay() {
    isVideoPlaying = true;
    updatePlayPauseButton(true);
}

// Handle video pause
function handleVideoPause() {
    isVideoPlaying = false;
    updatePlayPauseButton(false);
}

// Close lightbox
function closeLightbox() {
    const lightboxModal = document.getElementById('lightbox-modal');
    const videoPlayer = document.getElementById('video-player');
    const videoControls = document.querySelector('.video-controls');
    
    // Stop video playback
    if (videoPlayer) {
        videoPlayer.pause();
        videoPlayer.currentTime = 0;
    }
    
    // Hide video controls
    if (videoControls) {
        videoControls.style.display = 'none';
    }
    
    // Close lightbox
    if (lightboxModal) {
        lightboxModal.classList.remove('active');
    }
    
    document.body.style.overflow = 'auto';
    isLightboxOpen = false;
    isVideoPlaying = false;
}

// Show next media item
function showNextMedia() {
    if (mediaItems.length === 0) return;
    
    currentMediaIndex = (currentMediaIndex + 1) % mediaItems.length;
    openLightbox();
}

// Show previous media item
function showPreviousMedia() {
    if (mediaItems.length === 0) return;
    
    currentMediaIndex = (currentMediaIndex - 1 + mediaItems.length) % mediaItems.length;
    openLightbox();
}

// Update navigation buttons visibility
function updateNavigationButtons() {
    const lightboxPrev = document.querySelector('.lightbox-prev');
    const lightboxNext = document.querySelector('.lightbox-next');
    
    if (mediaItems.length <= 1) {
        // Hide navigation if only one item
        if (lightboxPrev) lightboxPrev.style.display = 'none';
        if (lightboxNext) lightboxNext.style.display = 'none';
    } else {
        // Show navigation
        if (lightboxPrev) lightboxPrev.style.display = 'flex';
        if (lightboxNext) lightboxNext.style.display = 'flex';
    }
}

// Handle keyboard navigation
function handleKeyboardNavigation(e) {
    if (!isLightboxOpen) return;
    
    switch(e.key) {
        case 'Escape':
            closeLightbox();
            break;
        case 'ArrowLeft':
            showPreviousMedia();
            break;
        case 'ArrowRight':
            showNextMedia();
            break;
        case ' ':
            // Play/pause video if video is active and controls are visible
            const currentItem = mediaItems[currentMediaIndex];
            const videoControls = document.querySelector('.video-controls');
            if (currentItem && currentItem.classList.contains('video') && videoControls && videoControls.style.display !== 'none') {
                e.preventDefault();
                togglePlayPause();
            }
            break;
    }
}

// ========================================
// VIDEO CONTROL FUNCTIONS
// ========================================

// Toggle play/pause
function togglePlayPause() {
    const videoPlayer = document.getElementById('video-player');
    if (!videoPlayer) return;
    
    if (videoPlayer.paused || videoPlayer.ended) {
        videoPlayer.play().then(() => {
            isVideoPlaying = true;
            updatePlayPauseButton(true);
        }).catch(error => {
            console.error('Play failed:', error);
            handleVideoError();
        });
    } else {
        videoPlayer.pause();
        isVideoPlaying = false;
        updatePlayPauseButton(false);
    }
}

// Toggle mute
function toggleMute() {
    const videoPlayer = document.getElementById('video-player');
    if (!videoPlayer) return;
    
    videoPlayer.muted = !videoPlayer.muted;
    updateMuteButton();
}

// Set video progress on click
function setVideoProgress(e) {
    const videoPlayer = document.getElementById('video-player');
    const videoProgress = document.querySelector('.video-progress');
    
    if (!videoPlayer || !videoProgress || !videoPlayer.duration) return;
    
    const rect = videoProgress.getBoundingClientRect();
    const percent = (e.clientX - rect.left) / rect.width;
    videoPlayer.currentTime = percent * videoPlayer.duration;
}

// Toggle fullscreen
function toggleFullscreen() {
    const lightboxVideo = document.querySelector('.lightbox-video');
    if (!lightboxVideo) return;
    
    if (!document.fullscreenElement && !document.webkitFullscreenElement) {
        if (lightboxVideo.requestFullscreen) {
            lightboxVideo.requestFullscreen();
        } else if (lightboxVideo.webkitRequestFullscreen) {
            lightboxVideo.webkitRequestFullscreen();
        }
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        }
    }
}

// Update video progress bar
function updateVideoProgress() {
    const videoPlayer = document.getElementById('video-player');
    const progressBar = document.querySelector('.progress-bar');
    const videoTime = document.querySelector('.video-time');
    
    if (!videoPlayer || !progressBar || !videoTime) return;
    
    if (videoPlayer.duration > 0) {
        const percent = (videoPlayer.currentTime / videoPlayer.duration) * 100;
        progressBar.style.width = `${percent}%`;
        
        const currentTime = formatTime(videoPlayer.currentTime);
        const duration = formatTime(videoPlayer.duration);
        videoTime.textContent = `${currentTime} / ${duration}`;
    }
}

// Update video duration display
function updateVideoDuration() {
    const videoPlayer = document.getElementById('video-player');
    const videoTime = document.querySelector('.video-time');
    
    if (videoPlayer && videoTime && videoPlayer.duration) {
        const duration = formatTime(videoPlayer.duration);
        videoTime.textContent = `0:00 / ${duration}`;
    }
}

// Format time (seconds to MM:SS)
function formatTime(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
}

// Update play/pause button
function updatePlayPauseButton(playing) {
    const playPauseBtn = document.querySelector('.play-pause');
    if (!playPauseBtn) return;
    
    playPauseBtn.innerHTML = playing ? 
        '<i class="bi bi-pause-fill"></i>' : 
        '<i class="bi bi-play-fill"></i>';
}

// Update mute button
function updateMuteButton() {
    const videoPlayer = document.getElementById('video-player');
    const muteBtn = document.querySelector('.mute-btn');
    
    if (!videoPlayer || !muteBtn) return;
    
    muteBtn.innerHTML = videoPlayer.muted ? 
        '<i class="bi bi-volume-mute-fill"></i>' : 
        '<i class="bi bi-volume-up-fill"></i>';
}

// Handle video ended
function handleVideoEnded() {
    isVideoPlaying = false;
    updatePlayPauseButton(false);
    
    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        progressBar.style.width = '100%';
    }
}

// Handle video error
function handleVideoError() {
    const videoPlayer = document.getElementById('video-player');
    const videoControls = document.querySelector('.video-controls');
    const videoError = document.querySelector('.lightbox-video .video-error-message');
    
    console.log('Video error occurred, hiding controls and showing error message');
    
    // Hide video player and controls
    if (videoPlayer) {
        videoPlayer.style.display = 'none';
    }
    if (videoControls) {
        videoControls.style.display = 'none';
    }
    
    // Show error message
    if (videoError) {
        videoError.style.display = 'flex';
    }
    
    updatePlayPauseButton(false);
    isVideoPlaying = false;
}

// Handle fullscreen change
function handleFullscreenChange() {
    const fullscreenBtn = document.querySelector('.fullscreen-btn');
    if (!fullscreenBtn) return;
    
    const isFullscreen = document.fullscreenElement || document.webkitFullscreenElement;
    
    fullscreenBtn.innerHTML = isFullscreen ? 
        '<i class="bi bi-fullscreen-exit"></i>' : 
        '<i class="bi bi-arrows-fullscreen"></i>';
}

// ========================================
// TOUCH SUPPORT FOR MOBILE
// ========================================

function initTouchSupport() {
    const lightboxModal = document.getElementById('lightbox-modal');
    let touchStartX = 0;
    let touchEndX = 0;
    
    if (!lightboxModal) return;
    
    lightboxModal.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });
    
    lightboxModal.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    }, { passive: true });
    
    function handleSwipe() {
        const swipeThreshold = 50;
        
        if (touchEndX < touchStartX - swipeThreshold) {
            // Swipe left - next media
            showNextMedia();
        }
        
        if (touchEndX > touchStartX + swipeThreshold) {
            // Swipe right - previous media
            showPreviousMedia();
        }
    }
}

// ========================================
// INITIALIZATION
// ========================================

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initLightbox();
    initTouchSupport();
});

// Export functions for global access
window.Lightbox = {
    open: openLightbox,
    close: closeLightbox,
    next: showNextMedia,
    prev: showPreviousMedia,
    togglePlayPause: togglePlayPause
};

console.log('Lightbox JS loaded successfully');
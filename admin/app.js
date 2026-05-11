/**
 * KATSS CMS - Admin SPA JavaScript
 * Complete application logic with video support
 */

const API_URL = 'api_simple.php';

// State management
let currentPage = 'dashboard';
let eventPage = 1;
let galleryPage = 1;
let teamPage = 1;
let announcementPage = 1;
let pendingDelete = null;

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', function() {
    const isLoggedIn = document.body.dataset.loggedIn === 'true';
    
    if (isLoggedIn) {
        initAdminPanel();
    }
    
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal') && e.target.classList.contains('open')) {
            e.target.classList.remove('open');
            document.body.style.overflow = '';
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });

    setupDragAndDrop();
});

function initAdminPanel() {
    loadStats();
    setupNavigation();
    setupSidebar();
}

// ==================== DRAG AND DROP ====================
function setupDragAndDrop() {
    document.querySelectorAll('.image-upload-area').forEach(area => {
        ['dragenter', 'dragover'].forEach(eventName => {
            area.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                area.style.borderColor = 'var(--primary)';
                area.style.background = '#f0f5ff';
            });
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            area.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                area.style.borderColor = 'var(--border)';
                area.style.background = '#fafafa';
            });
        });
        
        area.addEventListener('drop', function(e) {
            const input = area.querySelector('input[type="file"]');
            if (input && e.dataTransfer.files.length > 0) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change'));
            }
        });
    });
}

// ==================== NAVIGATION ====================
function setupNavigation() {
    document.querySelectorAll('.nav-item').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.nav-item').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            navigateTo(this.dataset.page);
        });
    });
    
    window.addEventListener('hashchange', function() {
        const hash = window.location.hash.replace('#', '');
        if (hash && document.getElementById(hash)) {
            navigateTo(hash);
        }
    });
    
    const hash = window.location.hash.replace('#', '');
    if (hash && document.getElementById(hash)) {
        navigateTo(hash);
    }
}

function navigateTo(page) {
    currentPage = page;
    window.location.hash = page;
    
    document.querySelectorAll('.page-content').forEach(p => p.classList.remove('active'));
    
    const target = document.getElementById(page);
    if (target) {
        target.classList.add('active');
    }
    
    document.querySelectorAll('.nav-item').forEach(link => {
        link.classList.toggle('active', link.dataset.page === page);
    });
    
    switch(page) {
        case 'dashboard': loadStats(); break;
        case 'events': loadEvents(); break;
        case 'gallery': loadGallery(); break;
        case 'management-team': loadTeam(); break;
        case 'announcements': loadAnnouncements(); break;
    }
    
    closeSidebar();
}

// ==================== SIDEBAR ====================
function setupSidebar() {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (toggle) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        });
    }
}

function closeSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('show');
}

// ==================== API CALLS ====================
async function apiCall(action, type, data = {}) {
    try {
        const url = API_URL + '?action=' + encodeURIComponent(action) + '&type=' + encodeURIComponent(type);
        
        let body;
        if (data instanceof FormData) {
            body = data;
        } else {
            body = new FormData();
            for (let key in data) {
                if (data.hasOwnProperty(key) && data[key] !== undefined && data[key] !== null) {
                    if (Array.isArray(data[key])) {
                        data[key].forEach(function(val) {
                            body.append(key + '[]', val);
                        });
                    } else {
                        body.append(key, data[key]);
                    }
                }
            }
        }
        
        const response = await fetch(url, {
            method: 'POST',
            body: body
        });
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            throw new Error('Server error: ' + text.substring(0, 100));
        }
        
        const result = await response.json();
        
        if (result.error) {
            throw new Error(result.error);
        }
        
        return result;
    } catch (error) {
        console.error('API Error:', error);
        showToast(error.message || 'Network error', 'error');
        return { error: error.message || 'Network error' };
    }
}

async function fetchJSON(url) {
    try {
        const response = await fetch(url);
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            throw new Error('Server error');
        }
        return await response.json();
    } catch (error) {
        console.error('Fetch error:', error);
        showToast(error.message || 'Network error', 'error');
        return { success: false, error: error.message };
    }
}

// ==================== DASHBOARD ====================
async function loadStats() {
    try {
        const result = await fetchJSON('api_simple.php?action=stats');
        if (result.success && result.data) {
            animateNumber('statTotalEvents', result.data.total_events);
            animateNumber('statFeaturedEvents', result.data.featured_events);
            animateNumber('statTotalGallery', result.data.total_gallery);
            animateNumber('statActiveGallery', result.data.active_gallery);
        }
    } catch (error) {
        console.error('Stats error:', error);
    }
}

function animateNumber(elementId, target) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const start = parseInt(element.textContent) || 0;
    const duration = 500;
    const startTime = performance.now();
    
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const current = Math.floor(start + (target - start) * progress);
        element.textContent = current;
        if (progress < 1) requestAnimationFrame(update);
    }
    
    requestAnimationFrame(update);
}

// ==================== EVENTS ====================
async function loadEvents(page = 1) {
    eventPage = page;
    const search = document.getElementById('eventSearch')?.value || '';
    const status = document.getElementById('eventStatusFilter')?.value || '';
    const category = document.getElementById('eventCategoryFilter')?.value || '';
    
    document.getElementById('eventsTableBody').innerHTML = 
        '<tr><td colspan="9" class="text-center"><div class="loading-spinner">Loading events...</div></td></tr>';
    
    try {
        const params = new URLSearchParams({ action: 'list', type: 'events', search, status, category, page });
        const result = await fetchJSON(API_URL + '?' + params.toString());
        
        if (result.success) {
            renderEventsTable(result.data);
            renderPagination('eventsPagination', result.pages, page, 'loadEvents');
            
            if (result.categories && document.getElementById('eventCategoryFilter')) {
                const select = document.getElementById('eventCategoryFilter');
                const currentValue = select.value;
                select.innerHTML = '<option value="">All Categories</option>';
                result.categories.forEach(cat => {
                    select.innerHTML += `<option value="${escapeHtml(cat)}">${escapeHtml(cat)}</option>`;
                });
                select.value = currentValue;
            }
        } else {
            throw new Error(result.error || 'Failed to load events');
        }
    } catch (error) {
        document.getElementById('eventsTableBody').innerHTML = 
            '<tr><td colspan="9" class="text-center text-error">Error: ' + escapeHtml(error.message) + '</td></tr>';
    }
}

function renderEventsTable(events) {
    const tbody = document.getElementById('eventsTableBody');
    
    if (!events || events.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">No events found</td></tr>';
        return;
    }
    
    tbody.innerHTML = events.map(event => `
        <tr>
            <td><input type="checkbox" value="${event.id}" class="event-checkbox"></td>
            <td>#${event.id}</td>
            <td>${event.image_url ? `<img src="${event.image_url}" class="table-img" alt="" loading="lazy" onerror="this.style.display='none'">` : '<span class="no-image">No img</span>'}</td>
            <td><strong>${escapeHtml(event.title)}</strong><br><small>${escapeHtml((event.excerpt || event.content || '').substring(0, 60))}...</small></td>
            <td>${escapeHtml(event.category || 'General')}</td>
            <td>${formatDate(event.event_date)}</td>
            <td><span class="badge badge-${event.status}">${event.status}</span></td>
            <td>${event.is_featured == 1 ? '<span class="badge badge-featured">★ Featured</span>' : '<span class="badge">-</span>'}</td>
            <td class="actions">
                <button class="btn-icon" onclick="editEvent(${event.id})" title="Edit"><i class="bi bi-pencil"></i></button>
                <button class="btn-icon" onclick="toggleEventStatus(${event.id})" title="Toggle Status"><i class="bi bi-eye${event.status === 'published' ? '-slash' : ''}"></i></button>
                <button class="btn-icon" onclick="toggleFeatured(${event.id})" title="Toggle Featured"><i class="bi bi-star${event.is_featured == 1 ? '-fill' : ''}"></i></button>
                <button class="btn-icon btn-delete" onclick="confirmDelete(${event.id}, 'events')" title="Delete"><i class="bi bi-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

async function saveEvent() {
    const id = document.getElementById('eventId').value;
    const title = document.getElementById('eventTitle').value.trim();
    const content = document.getElementById('eventContent').value.trim();
    const eventDate = document.getElementById('eventDate').value;
    
    if (!title) { showToast('Title is required', 'error'); return; }
    if (!content) { showToast('Content is required', 'error'); return; }
    if (!eventDate) { showToast('Date is required', 'error'); return; }
    
    let imageUrl = document.getElementById('eventImageUrl').value;
    const imageInput = document.getElementById('eventImageInput');
    
    if (imageInput.files.length > 0) {
        const formData = new FormData();
        formData.append('image', imageInput.files[0]);
        formData.append('type', 'events');
        
        const uploadResult = await apiCall('upload_image', 'events', formData);
        if (uploadResult.success) {
            imageUrl = uploadResult.file_path;
        } else {
            showToast(uploadResult.error || 'Upload failed', 'error');
            return;
        }
    }
    
    const data = {
        title, content,
        excerpt: document.getElementById('eventExcerpt').value.trim(),
        category: document.getElementById('eventCategory').value,
        event_date: eventDate,
        status: document.getElementById('eventStatus').value,
        is_featured: document.getElementById('eventFeatured').checked ? 1 : 0,
        image_url: imageUrl
    };
    
    const action = id ? 'update' : 'create';
    if (id) data.id = id;
    
    const result = await apiCall(action, 'events', data);
    
    if (result.success) {
        closeEventModal();
        loadEvents(eventPage);
        showToast(id ? 'Event updated!' : 'Event created!', 'success');
    }
}

async function editEvent(id) {
    try {
        const result = await fetchJSON(API_URL + '?action=list&type=events&page=1');
        
        if (result.success) {
            const event = result.data.find(e => e.id == id);
            if (event) {
                document.getElementById('eventId').value = event.id;
                document.getElementById('eventTitle').value = event.title || '';
                document.getElementById('eventContent').value = event.content || '';
                document.getElementById('eventExcerpt').value = event.excerpt || '';
                document.getElementById('eventCategory').value = event.category || 'General';
                document.getElementById('eventDate').value = event.event_date || '';
                document.getElementById('eventStatus').value = event.status || 'published';
                document.getElementById('eventFeatured').checked = event.is_featured == 1;
                
                const imageUrl = event.image_url || '';
                document.getElementById('eventImageUrl').value = imageUrl;
                const preview = document.getElementById('eventImagePreview');
                if (imageUrl) {
                    preview.querySelector('img').src = imageUrl;
                    preview.style.display = 'block';
                } else {
                    preview.style.display = 'none';
                }
                
                document.getElementById('eventImageInput').value = '';
                document.getElementById('eventModalTitle').textContent = 'Edit Event';
                openModal('eventModal');
            } else {
                showToast('Event not found', 'error');
            }
        }
    } catch (error) {
        showToast('Error loading event', 'error');
    }
}

async function toggleEventStatus(id) {
    const result = await apiCall('toggle_status', 'events', { id });
    if (result.success) {
        loadEvents(eventPage);
        showToast(`Status changed to ${result.new_status}`, 'success');
    }
}

async function toggleFeatured(id) {
    const result = await apiCall('toggle_featured', 'events', { id });
    if (result.success) {
        loadEvents(eventPage);
        showToast(`Featured ${result.is_featured ? 'enabled' : 'disabled'}`, 'success');
    }
}

// ==================== GALLERY MANAGEMENT ====================
async function loadGallery(page = 1) {
    galleryPage = page;
    const search = document.getElementById('gallerySearch')?.value || '';
    const status = document.getElementById('galleryStatusFilter')?.value || '';
    
    document.getElementById('galleryGrid').innerHTML = '<p class="text-center">Loading gallery...</p>';
    
    try {
        const params = new URLSearchParams({ action: 'list', type: 'gallery', search, status, page });
        const result = await fetchJSON(API_URL + '?' + params.toString());
        
        if (result.success) {
            renderGalleryGrid(result.data);
            renderPagination('galleryPagination', result.pages, page, 'loadGallery');
        } else {
            throw new Error(result.error || 'Failed to load gallery');
        }
    } catch (error) {
        document.getElementById('galleryGrid').innerHTML = 
            '<p class="text-center text-error">Error: ' + escapeHtml(error.message) + '</p>';
    }
}

function renderGalleryGrid(items) {
    const grid = document.getElementById('galleryGrid');
    
    if (!items || items.length === 0) {
        grid.innerHTML = `
            <div class="empty-state" style="grid-column: 1 / -1; padding: 40px; text-align: center;">
                <i class="bi bi-images" style="font-size: 48px; color: #ccc;"></i>
                <p>No gallery items found</p>
                <button class="btn btn-primary btn-sm" onclick="openGalleryModal()">
                    <i class="bi bi-plus-circle"></i> Add First Item
                </button>
            </div>`;
        return;
    }
    
    grid.innerHTML = items.map(item => {
        const imageUrl = item.media_url || item.file_path || item.thumbnail_path || '';
        const videoUrl = item.media_url || item.file_path || '';
        const thumbnailUrl = item.thumbnail_path || '';
        
        let previewHtml = '';
        
        if (item.media_type === 'video') {
            // Generate a colored frame for video
            const videoColors = ['#1a1a2e', '#16213e', '#0f3460', '#1a1a40', '#1b1b3a', '#2d2d5e'];
            const colorIndex = (item.id || 1) % videoColors.length;
            const bgColor = videoColors[colorIndex];
            
            // Extract initials from title
            const words = (item.title || 'Video').split(' ');
            const initials = words.length > 1 
                ? (words[0][0] + words[1][0]).toUpperCase()
                : (item.title || 'V').substring(0, 2).toUpperCase();
            
            if (thumbnailUrl) {
                // Has actual thumbnail image
                previewHtml = `
                    <div class="video-thumb-wrapper" onclick="previewVideo('${escapeHtml(videoUrl)}', '${escapeHtml(item.title)}')">
                        <img src="${thumbnailUrl}" alt="${escapeHtml(item.title)}" class="video-thumb-img" loading="lazy"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="video-frame-fallback" style="display:none; background: ${bgColor};">
                            <div class="video-frame-initials">${initials}</div>
                            <div class="video-frame-label">Video</div>
                        </div>
                        <div class="video-play-overlay">
                            <div class="play-button-circle">
                                <i class="bi bi-play-fill"></i>
                            </div>
                        </div>
                        <span class="video-type-badge"><i class="bi bi-camera-video-fill"></i> Video</span>
                    </div>`;
            } else {
                // No thumbnail - show colored frame with initials
                previewHtml = `
                    <div class="video-thumb-wrapper" onclick="previewVideo('${escapeHtml(videoUrl)}', '${escapeHtml(item.title)}')">
                        <div class="video-frame-fallback" style="background: ${bgColor}; display: flex;">
                            <div class="video-frame-initials">${initials}</div>
                            <div class="video-frame-label">Video</div>
                        </div>
                        <div class="video-play-overlay">
                            <div class="play-button-circle">
                                <i class="bi bi-play-fill"></i>
                            </div>
                        </div>
                        <span class="video-type-badge"><i class="bi bi-camera-video-fill"></i> Video</span>
                    </div>`;
            }
        } else {
            // Image type
            if (imageUrl) {
                previewHtml = `
                    <div class="image-thumb-wrapper">
                        <img src="${imageUrl}" alt="${escapeHtml(item.title)}" class="gallery-thumb-img" loading="lazy"
                             onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22180%22><rect fill=%22%23f0f0f0%22 width=%22300%22 height=%22180%22/><text fill=%22%23999%22 font-size=%2214%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22>No Image</text></svg>';">
                    </div>`;
            } else {
                previewHtml = `
                    <div class="image-thumb-wrapper">
                        <div class="image-placeholder-fallback">
                            <i class="bi bi-image"></i>
                            <span>No Image</span>
                        </div>
                    </div>`;
            }
        }
        
        const description = item.description || '';
        const truncatedDesc = description.length > 100 ? escapeHtml(description.substring(0, 100)) + '...' : escapeHtml(description);
        const category = item.category || '';
        const createdDate = item.created_at ? formatDate(item.created_at) : '';
        
        return `
        <div class="gallery-card" data-id="${item.id}">
            <div class="card-checkbox">
                <input type="checkbox" value="${item.id}" class="gallery-checkbox">
            </div>
            <div class="card-preview">
                ${previewHtml}
            </div>
            <div class="card-info">
                <div class="badges">
                    <span class="badge badge-${item.media_type || 'image'}">
                        <i class="bi bi-${item.media_type === 'video' ? 'film' : 'image'}"></i> 
                        ${(item.media_type || 'image') === 'video' ? 'Video' : 'Image'}
                    </span>
                    <span class="badge badge-${item.status || 'inactive'}">${item.status || 'inactive'}</span>
                </div>
                <h4 title="${escapeHtml(item.title)}">${escapeHtml(item.title.length > 50 ? item.title.substring(0, 50) + '...' : item.title)}</h4>
                ${description ? `<p class="card-desc">${truncatedDesc}</p>` : ''}
                ${category ? `<small class="card-meta"><i class="bi bi-tag"></i> ${escapeHtml(category)}</small>` : ''}
                ${createdDate ? `<small class="card-meta"><i class="bi bi-calendar"></i> ${createdDate}</small>` : ''}
                <div class="card-actions">
                    <button class="btn-icon btn-edit" onclick="event.stopPropagation(); editGalleryItem(${item.id})" title="Edit"><i class="bi bi-pencil"></i></button>
                    <button class="btn-icon btn-toggle" onclick="event.stopPropagation(); toggleGalleryStatus(${item.id})" title="Toggle"><i class="bi bi-eye${item.status === 'active' ? '-slash' : ''}"></i></button>
                    ${item.media_type === 'video' ? `
                        <button class="btn-icon btn-preview" onclick="event.stopPropagation(); previewVideo('${escapeHtml(videoUrl)}', '${escapeHtml(item.title)}')" title="Preview Video"><i class="bi bi-play-circle"></i></button>
                    ` : ''}
                    <button class="btn-icon btn-delete" onclick="event.stopPropagation(); confirmDelete(${item.id}, 'gallery')" title="Delete"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        </div>`;
    }).join('');
}

function previewVideo(videoUrl, title) {
    // Remove existing video modal
    const existingModal = document.querySelector('.video-preview-modal');
    if (existingModal) existingModal.remove();
    
    const modal = document.createElement('div');
    modal.className = 'modal video-preview-modal open';
    modal.innerHTML = `
        <div class="modal-content video-preview-content">
            <div class="modal-header" style="background: #111; color: white; display: flex; justify-content: space-between; align-items: center; padding: 15px 20px;">
                <h3 style="margin: 0; color: white;"><i class="bi bi-film"></i> ${escapeHtml(title)}</h3>
                <button class="modal-close" style="color: white; background: none; border: none; font-size: 28px; cursor: pointer;" onclick="this.closest('.modal').remove(); document.body.style.overflow = '';">&times;</button>
            </div>
            <div class="modal-body" style="padding: 0; background: #000;">
                <video controls autoplay style="width: 100%; max-height: 70vh; display: block;">
                    <source src="${videoUrl}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
            document.body.style.overflow = '';
        }
    });
}

async function saveGalleryItem() {
    const id = document.getElementById('galleryId').value;
    const title = document.getElementById('galleryTitle').value.trim();
    const mediaType = document.getElementById('galleryMediaType').value;
    
    if (!title) { showToast('Title is required', 'error'); return; }
    
    let filePath = document.getElementById('galleryFilePath').value;
    let thumbnailPath = '';
    const fileInput = document.getElementById('galleryImageInput');
    
    if (fileInput.files.length > 0) {
        const formData = new FormData();
        formData.append('image', fileInput.files[0]);
        formData.append('type', 'gallery');
        
        const uploadResult = await apiCall('upload_image', 'gallery', formData);
        if (uploadResult.success) {
            filePath = uploadResult.file_path || uploadResult.url || '';
            thumbnailPath = uploadResult.thumbnail_path || '';
        } else {
            showToast(uploadResult.error || 'Upload failed', 'error');
            return;
        }
    }
    
    const data = {
        title,
        description: document.getElementById('galleryDescription').value.trim(),
        media_type: mediaType,
        status: document.getElementById('galleryStatus').value,
        file_path: filePath,
        thumbnail_path: thumbnailPath,
        media_url: filePath,
        category: document.getElementById('galleryCategory')?.value || ''
    };
    
    const action = id ? 'update' : 'create';
    if (id) data.id = id;
    
    const result = await apiCall(action, 'gallery', data);
    
    if (result.success) {
        closeGalleryModal();
        loadGallery(galleryPage);
        showToast(id ? 'Gallery item updated!' : 'Gallery item created!', 'success');
    }
}

async function editGalleryItem(id) {
    try {
        const result = await fetchJSON(API_URL + '?action=list&type=gallery&page=1');
        
        if (result.success) {
            const item = result.data.find(g => g.id == id);
            if (item) {
                document.getElementById('galleryId').value = item.id;
                document.getElementById('galleryTitle').value = item.title || '';
                document.getElementById('galleryDescription').value = item.description || '';
                document.getElementById('galleryMediaType').value = item.media_type || 'image';
                document.getElementById('galleryStatus').value = item.status || 'active';
                
                if (item.category && document.getElementById('galleryCategory')) {
                    document.getElementById('galleryCategory').value = item.category;
                }
                
                const filePath = item.file_path || item.media_url || '';
                document.getElementById('galleryFilePath').value = filePath;
                const preview = document.getElementById('galleryImagePreview');
                
                if (filePath && item.media_type === 'image') {
                    preview.querySelector('img').src = filePath;
                    preview.style.display = 'block';
                } else {
                    preview.style.display = 'none';
                }
                
                document.getElementById('galleryImageInput').value = '';
                document.getElementById('galleryModalTitle').textContent = 'Edit Gallery Item';
                openModal('galleryModal');
            } else {
                showToast('Gallery item not found', 'error');
            }
        }
    } catch (error) {
        showToast('Error loading gallery item', 'error');
    }
}

async function toggleGalleryStatus(id) {
    const result = await apiCall('toggle_status', 'gallery', { id });
    if (result.success) {
        loadGallery(galleryPage);
        showToast(`Status changed to ${result.new_status}`, 'success');
    }
}

// ==================== MANAGEMENT TEAM ====================
async function loadTeam(page = 1) {
    teamPage = page;
    const search = document.getElementById('teamSearch')?.value || '';
    const status = document.getElementById('teamStatusFilter')?.value || '';
    
    document.getElementById('teamTableBody').innerHTML = 
        '<tr><td colspan="8" class="text-center">Loading...</td></tr>';
    
    try {
        const params = new URLSearchParams({ action: 'list', type: 'management_team', page, search, status });
        const result = await fetchJSON(API_URL + '?' + params.toString());
        
        if (result.success) {
            renderTeamTable(result.data);
            renderPagination('teamPagination', result.pages, page, 'loadTeam');
        } else {
            throw new Error(result.error || 'Failed to load team');
        }
    } catch (error) {
        document.getElementById('teamTableBody').innerHTML = 
            '<tr><td colspan="8" class="text-center text-error">Error: ' + escapeHtml(error.message) + '</td></tr>';
    }
}

function renderTeamTable(members) {
    const tbody = document.getElementById('teamTableBody');
    
    if (!members || members.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No team members found</td></tr>';
        return;
    }
    
    tbody.innerHTML = members.map(member => `
        <tr>
            <td><input type="checkbox" value="${member.id}" class="team-checkbox"></td>
            <td>#${member.id}</td>
            <td><strong>${escapeHtml(member.name)}</strong></td>
            <td>${escapeHtml(member.telephone)}</td>
            <td>${escapeHtml(member.post)}</td>
            <td><span class="badge badge-${member.status}">${member.status}</span></td>
            <td>${member.sort_order}</td>
            <td class="actions">
                <button class="btn-icon" onclick="editTeamMember(${member.id})" title="Edit"><i class="bi bi-pencil"></i></button>
                <button class="btn-icon" onclick="toggleTeamStatus(${member.id})" title="Toggle"><i class="bi bi-eye${member.status === 'active' ? '-slash' : ''}"></i></button>
                <button class="btn-icon btn-delete" onclick="confirmDelete(${member.id}, 'management_team')" title="Delete"><i class="bi bi-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

async function saveTeamMember() {
    const id = document.getElementById('teamId').value;
    const data = {
        name: document.getElementById('teamName').value.trim(),
        telephone: document.getElementById('teamTelephone').value.trim(),
        post: document.getElementById('teamPost').value.trim(),
        status: document.getElementById('teamStatus').value,
        sort_order: document.getElementById('teamSortOrder').value
    };
    
    if (!data.name || !data.telephone || !data.post) {
        showToast('Please fill all required fields', 'error');
        return;
    }
    
    const action = id ? 'update' : 'create';
    if (id) data.id = id;
    
    const result = await apiCall(action, 'management_team', data);
    
    if (result.success) {
        closeTeamModal();
        loadTeam(teamPage);
        showToast(id ? 'Team member updated!' : 'Team member created!', 'success');
    }
}

async function editTeamMember(id) {
    try {
        const result = await fetchJSON(API_URL + '?action=list&type=management_team&page=1');
        
        if (result.success) {
            const member = result.data.find(m => m.id == id);
            if (member) {
                document.getElementById('teamId').value = member.id;
                document.getElementById('teamName').value = member.name || '';
                document.getElementById('teamTelephone').value = member.telephone || '';
                document.getElementById('teamPost').value = member.post || '';
                document.getElementById('teamStatus').value = member.status || 'active';
                document.getElementById('teamSortOrder').value = member.sort_order || 0;
                
                document.getElementById('teamModalTitle').textContent = 'Edit Team Member';
                openModal('teamModal');
            } else {
                showToast('Team member not found', 'error');
            }
        }
    } catch (error) {
        showToast('Error loading team member', 'error');
    }
}

async function toggleTeamStatus(id) {
    const result = await apiCall('toggle_status', 'management_team', { id });
    if (result.success) {
        loadTeam(teamPage);
        showToast('Status updated', 'success');
    }
}

// ==================== ANNOUNCEMENTS ====================
async function loadAnnouncements(page = 1) {
    announcementPage = page;
    const search = document.getElementById('announcementSearch')?.value || '';
    const status = document.getElementById('announcementStatusFilter')?.value || '';
    const priority = document.getElementById('announcementPriorityFilter')?.value || '';
    
    document.getElementById('announcementTableBody').innerHTML = 
        '<tr><td colspan="8" class="text-center">Loading...</td></tr>';
    
    try {
        const params = new URLSearchParams({ action: 'list', type: 'announcements', page, search, status, priority });
        const result = await fetchJSON(API_URL + '?' + params.toString());
        
        if (result.success) {
            renderAnnouncementsTable(result.data);
            renderPagination('announcementPagination', result.pages, page, 'loadAnnouncements');
        } else {
            throw new Error(result.error || 'Failed to load announcements');
        }
    } catch (error) {
        document.getElementById('announcementTableBody').innerHTML = 
            '<tr><td colspan="8" class="text-center text-error">Error: ' + escapeHtml(error.message) + '</td></tr>';
    }
}

function renderAnnouncementsTable(announcements) {
    const tbody = document.getElementById('announcementTableBody');
    
    if (!announcements || announcements.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No announcements found</td></tr>';
        return;
    }
    
    tbody.innerHTML = announcements.map(a => `
        <tr>
            <td><input type="checkbox" value="${a.id}" class="announcement-checkbox"></td>
            <td>#${a.id}</td>
            <td><strong>${escapeHtml(a.title)}</strong></td>
            <td>${escapeHtml((a.content || '').substring(0, 100))}...</td>
            <td><span class="badge badge-${a.priority}">${a.priority}</span></td>
            <td><span class="badge badge-${a.status}">${a.status}</span></td>
            <td>${formatDate(a.created_at)}</td>
            <td class="actions">
                <button class="btn-icon" onclick="editAnnouncement(${a.id})" title="Edit"><i class="bi bi-pencil"></i></button>
                <button class="btn-icon" onclick="toggleAnnouncementStatus(${a.id})" title="Toggle"><i class="bi bi-eye${a.status === 'active' ? '-slash' : ''}"></i></button>
                <button class="btn-icon btn-delete" onclick="confirmDelete(${a.id}, 'announcements')" title="Delete"><i class="bi bi-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

async function saveAnnouncement() {
    const id = document.getElementById('announcementId').value;
    const data = {
        title: document.getElementById('announcementTitle').value.trim(),
        content: document.getElementById('announcementContent').value.trim(),
        priority: document.getElementById('announcementPriority').value,
        status: document.getElementById('announcementStatus').value
    };
    
    if (!data.title || !data.content) {
        showToast('Please fill all required fields', 'error');
        return;
    }
    
    const action = id ? 'update' : 'create';
    if (id) data.id = id;
    
    const result = await apiCall(action, 'announcements', data);
    
    if (result.success) {
        closeAnnouncementModal();
        loadAnnouncements(announcementPage);
        showToast(id ? 'Announcement updated!' : 'Announcement created!', 'success');
    }
}

async function editAnnouncement(id) {
    try {
        const result = await fetchJSON(API_URL + '?action=list&type=announcements&page=1');
        
        if (result.success) {
            const announcement = result.data.find(a => a.id == id);
            if (announcement) {
                document.getElementById('announcementId').value = announcement.id;
                document.getElementById('announcementTitle').value = announcement.title || '';
                document.getElementById('announcementContent').value = announcement.content || '';
                document.getElementById('announcementPriority').value = announcement.priority || 'medium';
                document.getElementById('announcementStatus').value = announcement.status || 'active';
                
                document.getElementById('announcementModalTitle').textContent = 'Edit Announcement';
                openModal('announcementModal');
            } else {
                showToast('Announcement not found', 'error');
            }
        }
    } catch (error) {
        showToast('Error loading announcement', 'error');
    }
}

async function toggleAnnouncementStatus(id) {
    const result = await apiCall('toggle_status', 'announcements', { id });
    if (result.success) {
        loadAnnouncements(announcementPage);
        showToast('Status updated', 'success');
    }
}

// ==================== BULK ACTIONS ====================
async function executeBulkAction(type) {
    const actionSelect = document.getElementById(type + 'BulkAction');
    if (!actionSelect) return;
    
    const action = actionSelect.value;
    if (!action) { showToast('Select an action', 'error'); return; }
    
    const checkboxClass = getCheckboxClass(type);
    const checkboxes = document.querySelectorAll('.' + checkboxClass + ':checked');
    
    if (checkboxes.length === 0) { showToast('Select items', 'error'); return; }
    
    if (!confirm('Are you sure?')) return;
    
    const ids = Array.from(checkboxes).map(cb => cb.value);
    const result = await apiCall('bulk_action', type, { ids, bulk_action: action });
    
    if (result.success) {
        showToast(result.message || 'Done', 'success');
        reloadCurrentPage();
        actionSelect.value = '';
    }
}

function getCheckboxClass(type) {
    const classes = { 'events': 'event-checkbox', 'gallery': 'gallery-checkbox', 'management-team': 'team-checkbox', 'announcements': 'announcement-checkbox' };
    return classes[type] || type + '-checkbox';
}

function reloadCurrentPage() {
    switch(currentPage) {
        case 'events': loadEvents(eventPage); break;
        case 'gallery': loadGallery(galleryPage); break;
        case 'management-team': loadTeam(teamPage); break;
        case 'announcements': loadAnnouncements(announcementPage); break;
        case 'dashboard': loadStats(); break;
    }
}

function toggleSelectAll(type) {
    const checkboxClass = getCheckboxClass(type);
    const selectAllId = type === 'events' ? 'selectAllEvents' : type === 'gallery' ? 'selectAllGallery' : type === 'management-team' ? 'selectAllTeam' : 'selectAllAnnouncements';
    const selectAll = document.getElementById(selectAllId);
    if (!selectAll) return;
    document.querySelectorAll('.' + checkboxClass).forEach(cb => cb.checked = selectAll.checked);
}

// ==================== DELETE ====================
function confirmDelete(id, type) {
    pendingDelete = { id, type };
    document.getElementById('confirmMessage').textContent = 'Are you sure you want to delete this item? This cannot be undone.';
    openModal('confirmModal');
    
    document.getElementById('confirmDeleteBtn').onclick = async function() {
        if (!pendingDelete) return;
        const result = await apiCall('delete', pendingDelete.type, { id: pendingDelete.id });
        if (result.success) {
            closeConfirmModal();
            reloadCurrentPage();
            showToast('Deleted successfully', 'success');
        }
        pendingDelete = null;
    };
}

// ==================== MODALS ====================
function openEventModal() {
    document.getElementById('eventForm').reset();
    document.getElementById('eventId').value = '';
    document.getElementById('eventImageUrl').value = '';
    document.getElementById('eventImagePreview').style.display = 'none';
    document.getElementById('eventImageInput').value = '';
    document.getElementById('eventModalTitle').textContent = 'Add New Event';
    document.getElementById('eventDate').value = new Date().toISOString().split('T')[0];
    openModal('eventModal');
}

function closeEventModal() { closeModal('eventModal'); }

function openGalleryModal() {
    document.getElementById('galleryForm').reset();
    document.getElementById('galleryId').value = '';
    document.getElementById('galleryFilePath').value = '';
    document.getElementById('galleryImagePreview').style.display = 'none';
    document.getElementById('galleryImageInput').value = '';
    document.getElementById('galleryModalTitle').textContent = 'Add Gallery Item';
    openModal('galleryModal');
}

function closeGalleryModal() { closeModal('galleryModal'); }

function openTeamModal(id) {
    if (id) {
        document.getElementById('teamModalTitle').textContent = 'Edit Team Member';
        editTeamMember(id);
    } else {
        document.getElementById('teamForm').reset();
        document.getElementById('teamId').value = '';
        document.getElementById('teamModalTitle').textContent = 'Add Team Member';
        openModal('teamModal');
    }
}

function closeTeamModal() { closeModal('teamModal'); }

function openAnnouncementModal(id) {
    if (id) {
        document.getElementById('announcementModalTitle').textContent = 'Edit Announcement';
        editAnnouncement(id);
    } else {
        document.getElementById('announcementForm').reset();
        document.getElementById('announcementId').value = '';
        document.getElementById('announcementModalTitle').textContent = 'Add Announcement';
        openModal('announcementModal');
    }
}

function closeAnnouncementModal() { closeModal('announcementModal'); }

function closeConfirmModal() { closeModal('confirmModal'); pendingDelete = null; }

function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }
}

function closeAllModals() {
    document.querySelectorAll('.modal.open').forEach(m => m.classList.remove('open'));
    document.body.style.overflow = '';
}

// ==================== IMAGE HANDLING ====================
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.querySelector('img').src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

function removeEventImage() {
    document.getElementById('eventImageInput').value = '';
    document.getElementById('eventImagePreview').style.display = 'none';
    document.getElementById('eventImageUrl').value = '';
}

function removeGalleryImage() {
    document.getElementById('galleryImageInput').value = '';
    document.getElementById('galleryImagePreview').style.display = 'none';
    document.getElementById('galleryFilePath').value = '';
}

// ==================== UTILITIES ====================
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function renderPagination(containerId, totalPages, currentPage, loadFnName) {
    const container = document.getElementById(containerId);
    if (!container || totalPages <= 1) { if (container) container.innerHTML = ''; return; }
    
    let html = '';
    if (currentPage > 1) html += `<button class="page-btn" onclick="${loadFnName}(${currentPage - 1})"><i class="bi bi-chevron-left"></i></button>`;
    
    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endPage = Math.min(totalPages, startPage + maxVisible - 1);
    if (endPage - startPage + 1 < maxVisible) startPage = Math.max(1, endPage - maxVisible + 1);
    
    if (startPage > 1) {
        html += `<button class="page-btn" onclick="${loadFnName}(1)">1</button>`;
        if (startPage > 2) html += '<span class="page-ellipsis">...</span>';
    }
    for (let i = startPage; i <= endPage; i++) {
        html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="${loadFnName}(${i})">${i}</button>`;
    }
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += '<span class="page-ellipsis">...</span>';
        html += `<button class="page-btn" onclick="${loadFnName}(${totalPages})">${totalPages}</button>`;
    }
    if (currentPage < totalPages) html += `<button class="page-btn" onclick="${loadFnName}(${currentPage + 1})"><i class="bi bi-chevron-right"></i></button>`;
    
    container.innerHTML = html;
}

function resetEventFilters() {
    document.getElementById('eventSearch').value = '';
    document.getElementById('eventStatusFilter').value = '';
    document.getElementById('eventCategoryFilter').value = '';
    loadEvents(1);
}

function resetGalleryFilters() {
    document.getElementById('gallerySearch').value = '';
    document.getElementById('galleryStatusFilter').value = '';
    loadGallery(1);
}

function resetTeamFilters() {
    document.getElementById('teamSearch').value = '';
    document.getElementById('teamStatusFilter').value = '';
    loadTeam(1);
}

function resetAnnouncementFilters() {
    document.getElementById('announcementSearch').value = '';
    document.getElementById('announcementStatusFilter').value = '';
    document.getElementById('announcementPriorityFilter').value = '';
    loadAnnouncements(1);
}

// ==================== TOAST ====================
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    if (toast._timeout) clearTimeout(toast._timeout);
    
    const icons = { 'success': '✓ ', 'error': '✗ ', 'warning': '⚠ ', 'info': 'ℹ ' };
    toast.textContent = (icons[type] || '') + message;
    toast.className = 'toast toast-' + type + ' show';
    
    toast._timeout = setTimeout(() => toast.classList.remove('show'), 4000);
}

// ==================== EXPORTS ====================
window.loadEvents = loadEvents;
window.loadGallery = loadGallery;
window.loadTeam = loadTeam;
window.loadAnnouncements = loadAnnouncements;
window.openEventModal = openEventModal;
window.closeEventModal = closeEventModal;
window.saveEvent = saveEvent;
window.editEvent = editEvent;
window.toggleEventStatus = toggleEventStatus;
window.toggleFeatured = toggleFeatured;
window.openGalleryModal = openGalleryModal;
window.closeGalleryModal = closeGalleryModal;
window.saveGalleryItem = saveGalleryItem;
window.editGalleryItem = editGalleryItem;
window.toggleGalleryStatus = toggleGalleryStatus;
window.previewVideo = previewVideo;
window.openTeamModal = openTeamModal;
window.closeTeamModal = closeTeamModal;
window.saveTeamMember = saveTeamMember;
window.editTeamMember = editTeamMember;
window.toggleTeamStatus = toggleTeamStatus;
window.openAnnouncementModal = openAnnouncementModal;
window.closeAnnouncementModal = closeAnnouncementModal;
window.saveAnnouncement = saveAnnouncement;
window.editAnnouncement = editAnnouncement;
window.toggleAnnouncementStatus = toggleAnnouncementStatus;
window.confirmDelete = confirmDelete;
window.closeConfirmModal = closeConfirmModal;
window.executeBulkAction = executeBulkAction;
window.toggleSelectAll = toggleSelectAll;
window.previewImage = previewImage;
window.removeEventImage = removeEventImage;
window.removeGalleryImage = removeGalleryImage;
window.resetEventFilters = resetEventFilters;
window.resetGalleryFilters = resetGalleryFilters;
window.resetTeamFilters = resetTeamFilters;
window.resetAnnouncementFilters = resetAnnouncementFilters;
window.showToast = showToast;
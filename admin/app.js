/**
 * KATSS CMS - Admin SPA JavaScript
 * Complete application logic with image handling
 */

const API_URL = 'api.php';

// State management
let currentPage = 'dashboard';
let eventPage = 1;
let galleryPage = 1;
let pendingDelete = null;

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', function() {
    const isLoggedIn = document.body.dataset.loggedIn === 'true';
    
    if (isLoggedIn) {
        initAdminPanel();
    }
    
    // Login form submission
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            // Let it submit normally for PHP session handling
        });
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });
    
    // Close modals when clicking overlay
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal') && e.target.classList.contains('open')) {
            e.target.classList.remove('open');
            document.body.style.overflow = '';
        }
    });

    // Drag and drop for image upload areas
    setupDragAndDrop();
});

function initAdminPanel() {
    loadStats();
    setupNavigation();
    setupSidebar();
}

// ==================== DRAG AND DROP ====================
function setupDragAndDrop() {
    const uploadAreas = document.querySelectorAll('.image-upload-area');
    
    uploadAreas.forEach(area => {
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
            
            // Update active state
            document.querySelectorAll('.nav-item').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            
            // Navigate to page
            const page = this.dataset.page;
            navigateTo(page);
        });
    });
    
    // Handle hash-based navigation
    window.addEventListener('hashchange', function() {
        const hash = window.location.hash.replace('#', '');
        if (hash && document.getElementById(hash)) {
            navigateTo(hash);
        }
    });
    
    // Load page from hash on initial load
    const hash = window.location.hash.replace('#', '');
    if (hash && document.getElementById(hash)) {
        navigateTo(hash);
    }
}

function navigateTo(page) {
    currentPage = page;
    
    // Update hash
    window.location.hash = page;
    
    // Hide all pages
    document.querySelectorAll('.page-content').forEach(p => p.classList.remove('active'));
    
    // Show target page
    const target = document.getElementById(page);
    if (target) {
        target.classList.add('active');
    }
    
    // Update nav active state
    document.querySelectorAll('.nav-item').forEach(link => {
        link.classList.toggle('active', link.dataset.page === page);
    });
    
    // Load page data
    switch(page) {
        case 'dashboard':
            loadStats();
            break;
        case 'events':
            loadEvents();
            break;
        case 'gallery':
            loadGallery();
            break;
    }
    
    // Close mobile sidebar
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

async function apiGet(action, type, params = {}) {
    try {
        const queryParams = new URLSearchParams();
        queryParams.append('action', action);
        queryParams.append('type', type);
        
        for (let key in params) {
            if (params.hasOwnProperty(key) && params[key] !== undefined && params[key] !== null && params[key] !== '') {
                queryParams.append(key, params[key]);
            }
        }
        
        const url = API_URL + '?' + queryParams.toString();
        
        const response = await fetch(url);
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

// ==================== DASHBOARD ====================
async function loadStats() {
    const result = await apiGet('stats', 'events');
    
    if (result.success && result.data) {
        animateNumber('statTotalEvents', result.data.total_events);
        animateNumber('statFeaturedEvents', result.data.featured_events);
        animateNumber('statTotalGallery', result.data.total_gallery);
        animateNumber('statActiveGallery', result.data.active_gallery);
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
        
        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }
    
    requestAnimationFrame(update);
}

// ==================== EVENTS MANAGEMENT ====================
async function loadEvents(page = 1) {
    eventPage = page;
    
    const search = document.getElementById('eventSearch')?.value || '';
    const status = document.getElementById('eventStatusFilter')?.value || '';
    const category = document.getElementById('eventCategoryFilter')?.value || '';
    
    // Show loading state
    document.getElementById('eventsTableBody').innerHTML = 
        '<tr><td colspan="9" class="text-center"><div class="loading-spinner">Loading...</div></td></tr>';
    
    const result = await apiGet('list', 'events', {
        search, status, category, page
    });
    
    if (result.success) {
        renderEventsTable(result.data);
        renderPagination('eventsPagination', result.pages, eventPage, 'loadEvents');
        
        // Update category filter
        if (result.categories && document.getElementById('eventCategoryFilter')) {
            const select = document.getElementById('eventCategoryFilter');
            const currentValue = select.value;
            select.innerHTML = '<option value="">All Categories</option>';
            result.categories.forEach(cat => {
                select.innerHTML += `<option value="${cat}">${cat}</option>`;
            });
            select.value = currentValue;
        }
    } else {
        document.getElementById('eventsTableBody').innerHTML = 
            '<tr><td colspan="9" class="text-center text-error">Failed to load events</td></tr>';
    }
}

function renderEventsTable(events) {
    const tbody = document.getElementById('eventsTableBody');
    
    if (!events || events.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center">
                    <div class="empty-state">
                        <i class="bi bi-megaphone"></i>
                        <p>No events found</p>
                        <button class="btn btn-primary btn-sm" onclick="openEventModal()">
                            <i class="bi bi-plus-circle"></i> Create Event
                        </button>
                    </div>
                </td>
            </tr>`;
        return;
    }
    
    tbody.innerHTML = events.map(event => {
        const imageHtml = event.image_url 
            ? `<img src="${event.image_url}" 
                     class="table-img" 
                     alt="${escapeHtml(event.title)}" 
                     loading="lazy"
                     onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <span class="no-image-fallback" style="display:none;width:56px;height:42px;align-items:center;justify-content:center;background:#f0f0f0;border-radius:4px;font-size:10px;color:#999;">No img</span>`
            : '<span class="no-image">No img</span>';
            
        return `
        <tr>
            <td><input type="checkbox" class="event-checkbox" value="${event.id}"></td>
            <td><span class="id-badge">#${event.id}</span></td>
            <td>${imageHtml}</td>
            <td>
                <strong class="event-title">${escapeHtml(event.title.substring(0, 50))}${event.title.length > 50 ? '...' : ''}</strong>
                <br><small class="event-excerpt">${escapeHtml((event.excerpt || event.content || '').substring(0, 60))}...</small>
            </td>
            <td><span class="category-tag">${escapeHtml(event.category || 'General')}</span></td>
            <td><span class="date-text">${formatDate(event.event_date)}</span></td>
            <td>
                <span class="badge badge-${event.status}">${event.status}</span>
            </td>
            <td>
                ${event.is_featured == 1 
                    ? '<span class="badge badge-featured"><i class="bi bi-star-fill"></i> Featured</span>' 
                    : '<span class="badge badge-not-featured">-</span>'}
            </td>
            <td class="actions">
                <button class="btn-icon btn-edit" onclick="editEvent(${event.id})" title="Edit Event">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn-icon btn-toggle" onclick="toggleEventStatus(${event.id})" title="${event.status === 'published' ? 'Move to Draft' : 'Publish'}">
                    <i class="bi bi-${event.status === 'published' ? 'eye-slash' : 'eye'}"></i>
                </button>
                <button class="btn-icon btn-featured" onclick="toggleFeatured(${event.id})" title="${event.is_featured == 1 ? 'Remove Featured' : 'Make Featured'}">
                    <i class="bi bi-star${event.is_featured == 1 ? '-fill' : ''}"></i>
                </button>
                <button class="btn-icon btn-delete" onclick="confirmDelete(${event.id}, 'events')" title="Delete Event">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>`;
    }).join('');
}

async function saveEvent() {
    const id = document.getElementById('eventId').value;
    
    // Validate
    const title = document.getElementById('eventTitle').value.trim();
    const content = document.getElementById('eventContent').value.trim();
    const eventDate = document.getElementById('eventDate').value;
    
    if (!title) { showToast('Title is required', 'error'); return; }
    if (!content) { showToast('Content is required', 'error'); return; }
    if (!eventDate) { showToast('Date is required', 'error'); return; }
    
    // Upload image first
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
        title: title,
        content: content,
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
    } else {
        showToast(result.error || 'Failed to save', 'error');
    }
}

async function editEvent(id) {
    // Show loading
    showToast('Loading event...', 'warning');
    
    const result = await apiGet('list', 'events', { page: 1, search: '' });
    
    if (result.success) {
        const event = result.data.find(e => e.id == id);
        if (event) {
            // Populate form fields
            document.getElementById('eventId').value = event.id;
            document.getElementById('eventTitle').value = event.title || '';
            document.getElementById('eventContent').value = event.content || '';
            document.getElementById('eventExcerpt').value = event.excerpt || '';
            document.getElementById('eventCategory').value = event.category || 'General';
            document.getElementById('eventDate').value = event.event_date || '';
            document.getElementById('eventStatus').value = event.status || 'published';
            document.getElementById('eventFeatured').checked = event.is_featured == 1;
            
            // Handle image display
            const imageUrl = event.image_url || '';
            document.getElementById('eventImageUrl').value = imageUrl;
            const preview = document.getElementById('eventImagePreview');
            const img = preview.querySelector('img');
            
            if (imageUrl) {
                img.src = imageUrl;
                img.onerror = function() {
                    preview.style.display = 'none';
                };
                img.onload = function() {
                    preview.style.display = 'block';
                };
            } else {
                preview.style.display = 'none';
            }
            
            // Clear file input
            document.getElementById('eventImageInput').value = '';
            
            // Update modal title
            document.getElementById('eventModalTitle').textContent = 'Edit Event';
            
            // Open modal
            openModal('eventModal');
            
            // Clear toast
            document.getElementById('toast').classList.remove('show');
        } else {
            showToast('Event not found', 'error');
        }
    } else {
        showToast('Failed to load event details', 'error');
    }
}

async function toggleEventStatus(id) {
    const result = await apiCall('toggle_status', 'events', { id });
    if (result.success) {
        loadEvents(eventPage);
        showToast(`Status changed to ${result.new_status}`, 'success');
    } else {
        showToast('Failed to toggle status', 'error');
    }
}

async function toggleFeatured(id) {
    const result = await apiCall('toggle_featured', 'events', { id });
    if (result.success) {
        loadEvents(eventPage);
        showToast(`Featured ${result.is_featured ? 'enabled' : 'disabled'}`, 'success');
    } else {
        showToast('Failed to toggle featured', 'error');
    }
}

// ==================== GALLERY MANAGEMENT ====================
async function loadGallery(page = 1) {
    galleryPage = page;
    
    const search = document.getElementById('gallerySearch')?.value || '';
    const status = document.getElementById('galleryStatusFilter')?.value || '';
    
    // Show loading
    document.getElementById('galleryGrid').innerHTML = '<p class="text-center"><div class="loading-spinner">Loading...</div></p>';
    
    const result = await apiGet('list', 'gallery', { search, status, page });
    
    if (result.success) {
        renderGalleryGrid(result.data);
        renderPagination('galleryPagination', result.pages, galleryPage, 'loadGallery');
    } else {
        document.getElementById('galleryGrid').innerHTML = '<p class="text-center text-error">Failed to load gallery items</p>';
    }
}

function renderGalleryGrid(items) {
    const grid = document.getElementById('galleryGrid');
    
    if (!items || items.length === 0) {
        grid.innerHTML = `
            <div class="empty-state" style="grid-column: 1 / -1;">
                <i class="bi bi-images"></i>
                <p>No gallery items found</p>
                <button class="btn btn-primary btn-sm" onclick="openGalleryModal()">
                    <i class="bi bi-plus-circle"></i> Add First Item
                </button>
            </div>`;
        return;
    }
    
    grid.innerHTML = items.map(item => {
        // Get the image URL from available columns (media_url first, then file_path, then thumbnail_path)
        const imageUrl = item.media_url || item.file_path || item.thumbnail_path || '';
        const videoUrl = item.media_url || item.file_path || '';
        
        let previewHtml = '';
        
        if (item.media_type === 'video') {
            previewHtml = `
                <div class="video-thumb">
                    ${videoUrl 
                        ? `<video muted preload="metadata" poster="${imageUrl}">
                             <source src="${videoUrl}" type="video/mp4">
                           </video>`
                        : '<div class="no-media"><i class="bi bi-film"></i></div>'}
                    <div class="play-icon"><i class="bi bi-play-circle-fill"></i></div>
                </div>`;
        } else {
            // For images, use imageUrl with fallback
            previewHtml = imageUrl
                ? `<img src="${imageUrl}" 
                       alt="${escapeHtml(item.title)}" 
                       loading="lazy"
                       onerror="this.onerror=null;this.src='https://placehold.co/300x180/002147/D4AF37?text=No+Image';">`
                : '<div class="no-media"><i class="bi bi-image"></i><p>No Image</p></div>';
        }
        
        // Get description safely
        const description = item.description || '';
        const truncatedDesc = description.length > 100 
            ? escapeHtml(description.substring(0, 100)) + '...' 
            : escapeHtml(description);
        
        // Get category
        const category = item.category || '';
        
        return `
        <div class="gallery-card" data-id="${item.id}">
            <div class="card-checkbox">
                <input type="checkbox" class="gallery-checkbox" value="${item.id}">
            </div>
            <div class="card-preview">
                ${previewHtml}
            </div>
            <div class="card-info">
                <div class="badges">
                    <span class="badge badge-${item.media_type || 'image'}">
                        <i class="bi bi-${item.media_type === 'video' ? 'film' : 'image'}"></i> 
                        ${(item.media_type || 'image').charAt(0).toUpperCase() + (item.media_type || 'image').slice(1)}
                    </span>
                    <span class="badge badge-${item.status || 'inactive'}">
                        ${(item.status || 'inactive').charAt(0).toUpperCase() + (item.status || 'inactive').slice(1)}
                    </span>
                </div>
                <h4 title="${escapeHtml(item.title)}">${escapeHtml(item.title)}</h4>
                ${description ? `<p>${truncatedDesc}</p>` : ''}
                ${category ? `<small class="category-text"><i class="bi bi-tag"></i> ${escapeHtml(category)}</small>` : ''}
                <div class="card-actions">
                    <button class="btn-icon btn-edit" onclick="editGalleryItem(${item.id})" title="Edit Item">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn-icon btn-toggle" onclick="toggleGalleryStatus(${item.id})" title="${item.status === 'active' ? 'Deactivate' : 'Activate'}">
                        <i class="bi bi-${item.status === 'active' ? 'eye-slash' : 'eye'}"></i>
                    </button>
                    <button class="btn-icon btn-delete" onclick="confirmDelete(${item.id}, 'gallery')" title="Delete Item">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>`;
    }).join('');
}

async function saveGalleryItem() {
    const id = document.getElementById('galleryId').value;
    const title = document.getElementById('galleryTitle').value.trim();
    
    if (!title) { showToast('Title is required', 'error'); return; }
    
    // Upload file first
    let filePath = document.getElementById('galleryFilePath').value;
    const fileInput = document.getElementById('galleryImageInput');
    
    if (fileInput.files.length > 0) {
        const formData = new FormData();
        formData.append('image', fileInput.files[0]);
        formData.append('type', 'gallery');
        
        const uploadResult = await apiCall('upload_image', 'gallery', formData);
        if (uploadResult.success) {
            filePath = uploadResult.file_path;
        } else {
            showToast(uploadResult.error || 'Upload failed', 'error');
            return;
        }
    }
    
    const data = {
        title: title,
        description: document.getElementById('galleryDescription').value.trim(),
        media_type: document.getElementById('galleryMediaType').value,
        status: document.getElementById('galleryStatus').value,
        file_path: filePath,
        category: document.getElementById('galleryCategory')?.value || ''
    };
    
    const action = id ? 'update' : 'create';
    if (id) data.id = id;
    
    const result = await apiCall(action, 'gallery', data);
    
    if (result.success) {
        closeGalleryModal();
        loadGallery(galleryPage);
        showToast(id ? 'Item updated!' : 'Item created!', 'success');
    } else {
        showToast(result.error || 'Failed to save', 'error');
    }
}

async function editGalleryItem(id) {
    showToast('Loading...', 'warning');
    
    const result = await apiGet('list', 'gallery', { page: 1 });
    
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
            
            // Handle image preview
            const filePath = item.file_path || item.thumbnail_path || '';
            document.getElementById('galleryFilePath').value = filePath;
            const preview = document.getElementById('galleryImagePreview');
            const img = preview.querySelector('img');
            
            if (filePath) {
                img.src = filePath;
                img.onerror = function() {
                    preview.style.display = 'none';
                };
                img.onload = function() {
                    preview.style.display = 'block';
                };
            } else {
                preview.style.display = 'none';
            }
            
            document.getElementById('galleryImageInput').value = '';
            document.getElementById('galleryModalTitle').textContent = 'Edit Gallery Item';
            openModal('galleryModal');
            
            document.getElementById('toast').classList.remove('show');
        } else {
            showToast('Gallery item not found', 'error');
        }
    } else {
        showToast('Failed to load gallery item', 'error');
    }
}

async function toggleGalleryStatus(id) {
    const result = await apiCall('toggle_status', 'gallery', { id });
    if (result.success) {
        loadGallery(galleryPage);
        showToast(`Status changed to ${result.new_status}`, 'success');
    } else {
        showToast('Failed to toggle status', 'error');
    }
}

// ==================== IMAGE UPLOAD ====================
async function uploadImage(file, type) {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('type', type);
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Upload error:', error);
        return { error: 'Upload failed' };
    }
}

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = preview.querySelector('img');
            if (img) {
                img.src = e.target.result;
                img.onerror = function() {
                    preview.style.display = 'none';
                };
                preview.style.display = 'block';
            }
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

// ==================== BULK ACTIONS ====================
async function executeBulkAction(type) {
    const actionSelect = document.getElementById(type + 'BulkAction');
    if (!actionSelect) return;
    
    const action = actionSelect.value;
    
    if (!action) {
        showToast('Please select an action', 'error');
        return;
    }
    
    const checkboxClass = type === 'events' ? 'event-checkbox' : 'gallery-checkbox';
    const checkboxes = document.querySelectorAll('.' + checkboxClass + ':checked');
    
    if (checkboxes.length === 0) {
        showToast('Please select at least one item', 'error');
        return;
    }
    
    const actionLabels = {
        'publish': 'publish',
        'draft': 'move to draft',
        'activate': 'activate',
        'deactivate': 'deactivate',
        'delete': 'delete'
    };
    
    const actionLabel = actionLabels[action] || action;
    
    if (!confirm(`Are you sure you want to ${actionLabel} ${checkboxes.length} item(s)?`)) {
        return;
    }
    
    const ids = Array.from(checkboxes).map(cb => cb.value);
    
    const result = await apiCall('bulk_action', type, {
        ids: ids,
        bulk_action: action
    });
    
    if (result.success) {
        showToast(result.message || 'Action completed', 'success');
        if (type === 'events') {
            loadEvents(eventPage);
        } else {
            loadGallery(galleryPage);
        }
    } else {
        showToast(result.error || 'Action failed', 'error');
    }
    
    // Reset select
    actionSelect.value = '';
}

function toggleSelectAll(type) {
    const checkboxClass = type === 'events' ? 'event-checkbox' : 'gallery-checkbox';
    const selectAllId = type === 'events' ? 'selectAllEvents' : 'selectAllGallery';
    
    const selectAll = document.getElementById(selectAllId);
    if (!selectAll) return;
    
    const checkboxes = document.querySelectorAll('.' + checkboxClass);
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
}

// ==================== DELETE CONFIRMATION ====================
function confirmDelete(id, type) {
    pendingDelete = { id, type };
    
    const typeLabel = type === 'events' ? 'event' : 'gallery item';
    document.getElementById('confirmMessage').textContent = 
        'Are you sure you want to delete this ' + typeLabel + '?';
    
    openModal('confirmModal');
    
    document.getElementById('confirmDeleteBtn').onclick = async function() {
        if (!pendingDelete) return;
        
        const result = await apiCall('delete', pendingDelete.type, { 
            id: pendingDelete.id 
        });
        
        if (result.success) {
            closeConfirmModal();
            if (pendingDelete.type === 'events') loadEvents(eventPage);
            else loadGallery(galleryPage);
            showToast('Deleted successfully', 'success');
        } else {
            showToast(result.error || 'Failed to delete', 'error');
        }
        pendingDelete = null;
    };
}

// ==================== MODALS ====================
function openEventModal() {
    // Reset form
    document.getElementById('eventForm').reset();
    document.getElementById('eventId').value = '';
    document.getElementById('eventImageUrl').value = '';
    document.getElementById('eventImagePreview').style.display = 'none';
    document.getElementById('eventImageInput').value = '';
    document.getElementById('eventModalTitle').textContent = 'Add New Event';
    
    // Set default date to today
    document.getElementById('eventDate').value = new Date().toISOString().split('T')[0];
    
    openModal('eventModal');
}

function closeEventModal() {
    closeModal('eventModal');
}

function openGalleryModal() {
    document.getElementById('galleryForm').reset();
    document.getElementById('galleryId').value = '';
    document.getElementById('galleryFilePath').value = '';
    document.getElementById('galleryImagePreview').style.display = 'none';
    document.getElementById('galleryImageInput').value = '';
    document.getElementById('galleryModalTitle').textContent = 'Add Gallery Item';
    
    openModal('galleryModal');
}

function closeGalleryModal() {
    closeModal('galleryModal');
}

function closeConfirmModal() {
    closeModal('confirmModal');
    pendingDelete = null;
}

function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
        
        // Focus first input if exists
        setTimeout(() => {
            const firstInput = modal.querySelector('input:not([type="hidden"])');
            if (firstInput) firstInput.focus();
        }, 100);
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
    document.querySelectorAll('.modal.open').forEach(modal => {
        modal.classList.remove('open');
    });
    document.body.style.overflow = '';
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
    
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

function renderPagination(containerId, totalPages, currentPage, loadFnName) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '';
    
    // Previous button
    if (currentPage > 1) {
        html += `<button class="page-btn" onclick="${loadFnName}(${currentPage - 1})" title="Previous">
                    <i class="bi bi-chevron-left"></i>
                 </button>`;
    }
    
    // Page numbers
    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endPage = Math.min(totalPages, startPage + maxVisible - 1);
    
    if (endPage - startPage + 1 < maxVisible) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }
    
    if (startPage > 1) {
        html += `<button class="page-btn" onclick="${loadFnName}(1)">1</button>`;
        if (startPage > 2) html += '<span class="page-ellipsis">...</span>';
    }
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" 
                        onclick="${loadFnName}(${i})">${i}</button>`;
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += '<span class="page-ellipsis">...</span>';
        html += `<button class="page-btn" onclick="${loadFnName}(${totalPages})">${totalPages}</button>`;
    }
    
    // Next button
    if (currentPage < totalPages) {
        html += `<button class="page-btn" onclick="${loadFnName}(${currentPage + 1})" title="Next">
                    <i class="bi bi-chevron-right"></i>
                 </button>`;
    }
    
    container.innerHTML = html;
}

function resetEventFilters() {
    const searchEl = document.getElementById('eventSearch');
    const statusEl = document.getElementById('eventStatusFilter');
    const categoryEl = document.getElementById('eventCategoryFilter');
    
    if (searchEl) searchEl.value = '';
    if (statusEl) statusEl.value = '';
    if (categoryEl) categoryEl.value = '';
    
    loadEvents(1);
}

function resetGalleryFilters() {
    const searchEl = document.getElementById('gallerySearch');
    const statusEl = document.getElementById('galleryStatusFilter');
    
    if (searchEl) searchEl.value = '';
    if (statusEl) statusEl.value = '';
    
    loadGallery(1);
}

function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    const icon = btn.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.className = 'bi bi-eye-slash-fill';
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.className = 'bi bi-eye-fill';
        }
    }
}

// ==================== TOAST NOTIFICATIONS ====================
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    
    // Clear any existing timeout
    if (toast._timeout) {
        clearTimeout(toast._timeout);
    }
    
    // Set icon based on type
    let icon = '';
    switch(type) {
        case 'success': icon = '<i class="bi bi-check-circle-fill"></i> '; break;
        case 'error': icon = '<i class="bi bi-exclamation-circle-fill"></i> '; break;
        case 'warning': icon = '<i class="bi bi-exclamation-triangle-fill"></i> '; break;
        case 'info': icon = '<i class="bi bi-info-circle-fill"></i> '; break;
    }
    
    toast.innerHTML = icon + message;
    toast.className = 'toast toast-' + type + ' show';
    
    // Auto hide after 4 seconds
    toast._timeout = setTimeout(() => {
        toast.classList.remove('show');
    }, 4000);
}

// ==================== KEYBOARD SHORTCUTS ====================
document.addEventListener('keydown', function(e) {
    // Ctrl+N or Alt+N - New Event
    if ((e.ctrlKey || e.altKey) && e.key === 'n') {
        e.preventDefault();
        if (currentPage === 'events') {
            openEventModal();
        } else if (currentPage === 'gallery') {
            openGalleryModal();
        }
    }
    
    // Ctrl+D - Go to Dashboard
    if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
        e.preventDefault();
        navigateTo('dashboard');
    }
    
    // Ctrl+E - Go to Events
    if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
        e.preventDefault();
        navigateTo('events');
    }
    
    // Ctrl+G - Go to Gallery
    if ((e.ctrlKey || e.metaKey) && e.key === 'g') {
        e.preventDefault();
        navigateTo('gallery');
    }
    
    // F5 or Ctrl+R - Refresh current page
    if (e.key === 'F5' || ((e.ctrlKey || e.metaKey) && e.key === 'r')) {
        // Let browser handle normally
    }
});

// Handle window resize for responsive adjustments
let resizeTimeout;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(function() {
        closeSidebar();
    }, 250);
});

// Export functions for global access
window.loadEvents = loadEvents;
window.loadGallery = loadGallery;
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
window.confirmDelete = confirmDelete;
window.closeConfirmModal = closeConfirmModal;
window.executeBulkAction = executeBulkAction;
window.toggleSelectAll = toggleSelectAll;
window.previewImage = previewImage;
window.removeEventImage = removeEventImage;
window.removeGalleryImage = removeGalleryImage;
window.resetEventFilters = resetEventFilters;
window.resetGalleryFilters = resetGalleryFilters;
window.togglePassword = togglePassword;
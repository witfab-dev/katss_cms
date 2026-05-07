/**
 * KATSS CMS - Admin SPA JavaScript * Handles all admin functionality
 */

const API_URL = 'api.php';

// State
let currentPage = 'dashboard';
let eventPage = 1, galleryPage = 1;
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
            // Let it submit normally for PHP session
        });
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });
});

function initAdminPanel() {
    loadStats();
    setupNavigation();
    setupSidebar();
}

// ==================== NAVIGATION ====================
function setupNavigation() {
    document.querySelectorAll('.nav-item').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            document.querySelectorAll('.nav-item').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            
            const page = this.dataset.page;
            navigateTo(page);
        });
    });
}

function navigateTo(page) {
    currentPage = page;
    
    // Hide all pages
    document.querySelectorAll('.page-content').forEach(p => p.classList.remove('active'));
    
    // Show target page
    const target = document.getElementById(page);
    if (target) target.classList.add('active');
    
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
    
    toggle.addEventListener('click', function() {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    });
    
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    });
}

function closeSidebar() {
    document.getElementById('adminSidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
}

// ==================== API CALLS ====================
async function apiCall(action, type, data = {}) {
    try {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('type', type);
        
        if (data instanceof FormData) {
            for (let [key, value] of data.entries()) {
                formData.append(key, value);
            }
        } else {
            for (let key in data) {
                formData.append(key, data[key]);
            }
        }
        
        const response = await fetch(API_URL + '?action=' + action + '&type=' + type, {
            method: 'POST',
            body: formData
        });
        
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        showToast('Network error. Please try again.', 'error');
        return { error: 'Network error' };
    }
}

async function apiGet(action, type, params = {}) {
    try {
        const queryString = new URLSearchParams({ action, type, ...params }).toString();
        const response = await fetch(API_URL + '?' + queryString);
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        showToast('Network error. Please try again.', 'error');
        return { error: 'Network error' };
    }
}

// ==================== DASHBOARD ====================
async function loadStats() {
    const result = await apiGet('stats', 'events');
    
    if (result.success && result.data) {
        document.getElementById('statTotalEvents').textContent = result.data.total_events;
        document.getElementById('statFeaturedEvents').textContent = result.data.featured_events;
        document.getElementById('statTotalGallery').textContent = result.data.total_gallery;
        document.getElementById('statActiveGallery').textContent = result.data.active_gallery;
    }
}

// ==================== EVENTS ====================
async function loadEvents(page = 1) {
    eventPage = page;
    const search = document.getElementById('eventSearch').value;
    const status = document.getElementById('eventStatusFilter').value;
    const category = document.getElementById('eventCategoryFilter').value;
    
    const result = await apiGet('list', 'events', {
        search, status, category, page
    });
    
    if (result.success) {
        renderEventsTable(result.data);
        renderPagination('eventsPagination', result.pages, eventPage, loadEvents);
        
        // Update category filter
        if (result.categories) {
            const select = document.getElementById('eventCategoryFilter');
            const currentValue = select.value;
            select.innerHTML = '<option value="">All Categories</option>';
            result.categories.forEach(cat => {
                select.innerHTML += `<option value="${cat}">${cat}</option>`;
            });
            select.value = currentValue;
        }
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
            <td><input type="checkbox" class="event-checkbox" value="${event.id}"></td>
            <td>${event.id}</td>
            <td>
                ${event.image_url 
                    ? `<img src="${event.image_url}" class="table-img" alt="" onerror="this.style.display='none'">` 
                    : '<span class="no-image">No img</span>'}
            </td>
            <td>
                <strong>${escapeHtml(event.title.substring(0, 50))}</strong>
                <br><small>${escapeHtml((event.excerpt || event.content).substring(0, 60))}...</small>
            </td>
            <td>${escapeHtml(event.category || 'General')}</td>
            <td>${formatDate(event.event_date)}</td>
            <td>
                <span class="badge badge-${event.status}">${event.status}</span>
            </td>
            <td>
                ${event.is_featured ? '<span class="badge badge-featured"><i class="bi bi-star-fill"></i> Featured</span>' : ''}
            </td>
            <td class="actions">
                <button class="btn-icon btn-edit" onclick="editEvent(${event.id})" title="Edit">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn-icon btn-toggle" onclick="toggleEventStatus(${event.id})" title="Toggle Status">
                    <i class="bi bi-eye${event.status === 'published' ? '-slash' : ''}"></i>
                </button>
                <button class="btn-icon btn-featured" onclick="toggleFeatured(${event.id})" title="Toggle Featured">
                    <i class="bi bi-star${event.is_featured ? '-fill' : ''}"></i>
                </button>
                <button class="btn-icon btn-delete" onclick="confirmDelete(${event.id}, 'event')" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

async function saveEvent() {
    const id = document.getElementById('eventId').value;
    const data = {
        title: document.getElementById('eventTitle').value,
        content: document.getElementById('eventContent').value,
        excerpt: document.getElementById('eventExcerpt').value,
        category: document.getElementById('eventCategory').value,
        event_date: document.getElementById('eventDate').value,
        status: document.getElementById('eventStatus').value,
        is_featured: document.getElementById('eventFeatured').checked ? 1 : 0,
        image_url: document.getElementById('eventImageUrl').value
    };
    
    if (!data.title || !data.content || !data.event_date) {
        showToast('Title, content, and date are required', 'error');
        return;
    }
    
    // Upload image if selected
    const imageInput = document.getElementById('eventImageInput');
    if (imageInput.files.length > 0) {
        const uploadResult = await uploadImage(imageInput.files[0], 'events');
        if (uploadResult.success) {
            data.image_url = uploadResult.file_path;
        } else {
            showToast(uploadResult.error || 'Image upload failed', 'error');
            return;
        }
    }
    
    const action = id ? 'update' : 'create';
    if (id) data.id = id;
    
    const result = await apiCall(action, 'events', data);
    
    if (result.success) {
        closeEventModal();
        loadEvents(eventPage);
        showToast(id ? 'Event updated!' : 'Event created!', 'success');
    } else {
        showToast(result.error || 'Failed to save event', 'error');
    }
}

function editEvent(id) {
    // Fetch event details and populate form
    apiGet('list', 'events', { search: '', page: 1 }).then(result => {
        if (result.success) {
            const event = result.data.find(e => e.id == id);
            if (event) {
                document.getElementById('eventId').value = event.id;
                document.getElementById('eventTitle').value = event.title;
                document.getElementById('eventContent').value = event.content;
                document.getElementById('eventExcerpt').value = event.excerpt || '';
                document.getElementById('eventCategory').value = event.category || 'General';
                document.getElementById('eventDate').value = event.event_date;
                document.getElementById('eventStatus').value = event.status;
                document.getElementById('eventFeatured').checked = event.is_featured == 1;
                document.getElementById('eventImageUrl').value = event.image_url || '';
                
                // Show existing image
                if (event.image_url) {
                    const preview = document.getElementById('eventImagePreview');
                    preview.querySelector('img').src = event.image_url;
                    preview.style.display = 'block';
                }
                
                document.getElementById('eventModalTitle').textContent = 'Edit Event';
                openModal('eventModal');
            }
        }
    });
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

// ==================== GALLERY ====================
async function loadGallery(page = 1) {
    galleryPage = page;
    const search = document.getElementById('gallerySearch').value;
    const status = document.getElementById('galleryStatusFilter').value;
    
    const result = await apiGet('list', 'gallery', { search, status, page });
    
    if (result.success) {
        renderGalleryGrid(result.data);
        renderPagination('galleryPagination', result.pages, galleryPage, loadGallery);
    }
}

function renderGalleryGrid(items) {
    const grid = document.getElementById('galleryGrid');
    
    if (!items || items.length === 0) {
        grid.innerHTML = '<p class="text-center no-data">No gallery items found. <br><button class="btn btn-primary" onclick="openGalleryModal()">Add First Item</button></p>';
        return;
    }
    
    grid.innerHTML = items.map(item => `
        <div class="gallery-card" data-id="${item.id}">
            <div class="card-checkbox">
                <input type="checkbox" class="gallery-checkbox" value="${item.id}">
            </div>
            <div class="card-preview">
                ${item.media_type === 'video' 
                    ? `<div class="video-thumb"><video muted><source src="${item.file_path}" type="video/mp4"></video><div class="play-icon"><i class="bi bi-play-circle-fill"></i></div></div>`
                    : `<img src="${item.file_path || item.thumbnail_path || 'placeholder.jpg'}" alt="${escapeHtml(item.title)}" onerror="this.style.display='none'">`
                }
            </div>
            <div class="card-info">
                <div class="badges">
                    <span class="badge badge-${item.media_type}">${item.media_type}</span>
                    <span class="badge badge-${item.status}">${item.status}</span>
                </div>
                <h4>${escapeHtml(item.title)}</h4>
                <p>${escapeHtml((item.description || '').substring(0, 100))}</p>
                <div class="card-actions">
                    <button class="btn-icon btn-edit" onclick="editGalleryItem(${item.id})"><i class="bi bi-pencil"></i></button>
                    <button class="btn-icon btn-toggle" onclick="toggleGalleryStatus(${item.id})"><i class="bi bi-${item.status === 'active' ? 'eye-slash' : 'eye'}"></i></button>
                    <button class="btn-icon btn-delete" onclick="confirmDelete(${item.id}, 'gallery')"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        </div>
    `).join('');
}

async function saveGalleryItem() {
    const id = document.getElementById('galleryId').value;
    const data = {
        title: document.getElementById('galleryTitle').value,
        description: document.getElementById('galleryDescription').value,
        media_type: document.getElementById('galleryMediaType').value,
        status: document.getElementById('galleryStatus').value,
        file_path: document.getElementById('galleryFilePath').value
    };
    
    if (!data.title) {
        showToast('Title is required', 'error');
        return;
    }
    
    // Upload file if selected
    const fileInput = document.getElementById('galleryImageInput');
    if (fileInput.files.length > 0) {
        const uploadResult = await uploadImage(fileInput.files[0], 'gallery');
        if (uploadResult.success) {
            data.file_path = uploadResult.file_path;
        } else {
            showToast(uploadResult.error || 'Upload failed', 'error');
            return;
        }
    }
    
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

async function toggleGalleryStatus(id) {
    const result = await apiCall('toggle_status', 'gallery', { id });
    if (result.success) {
        loadGallery(galleryPage);
        showToast(`Status changed to ${result.new_status}`, 'success');
    }
}

// ==================== IMAGE UPLOAD ====================
async function uploadImage(file, type) {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('type', type);
    
    const result = await apiCall('upload_image', type, formData);
    return result;
}

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

// ==================== BULK ACTIONS ====================
async function executeBulkAction(type) {
    const actionSelect = document.getElementById(type + 'BulkAction');
    const action = actionSelect.value;
    
    if (!action) {
        showToast('Please select an action', 'error');
        return;
    }
    
    const checkboxes = document.querySelectorAll('.' + type + '-checkbox:checked');
    if (checkboxes.length === 0) {
        showToast('Please select items', 'error');
        return;
    }
    
    if (!confirm(`Are you sure you want to ${action} ${checkboxes.length} item(s)?`)) return;
    
    const ids = Array.from(checkboxes).map(cb => cb.value);
    
    const result = await apiCall('bulk_action', type, { ids, bulk_action: action });
    
    if (result.success) {
        showToast(result.message, 'success');
        if (type === 'events') loadEvents(eventPage);
        else loadGallery(galleryPage);
    } else {
        showToast(result.error || 'Action failed', 'error');
    }
    
    actionSelect.value = '';
}

function toggleSelectAll(type) {
    const selectAll = document.getElementById('selectAll' + type.charAt(0).toUpperCase() + type.slice(1));
    const checkboxes = document.querySelectorAll('.' + type + '-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
}

// ==================== DELETE ====================
function confirmDelete(id, type) {
    pendingDelete = { id, type };
    document.getElementById('confirmMessage').textContent = 
        `Are you sure you want to delete this ${type === 'events' ? 'event' : 'gallery item'}? This cannot be undone.`;
    openModal('confirmModal');
    
    document.getElementById('confirmDeleteBtn').onclick = async function() {
        const result = await apiCall('delete', pendingDelete.type, { ids: [pendingDelete.id] });
        if (result.success) {
            closeConfirmModal();
            if (pendingDelete.type === 'events') loadEvents(eventPage);
            else loadGallery(galleryPage);
            showToast('Item deleted', 'success');
        } else {
            showToast(result.error || 'Failed to delete', 'error');
        }
    };
}

// ==================== MODALS ====================
function openEventModal() {
    document.getElementById('eventForm').reset();
    document.getElementById('eventId').value = '';
    document.getElementById('eventModalTitle').textContent = 'Add Event';
    document.getElementById('eventImagePreview').style.display = 'none';
    openModal('eventModal');
}

function closeEventModal() {
    closeModal('eventModal');
}

function openGalleryModal() {
    document.getElementById('galleryForm').reset();
    document.getElementById('galleryId').value = '';
    document.getElementById('galleryModalTitle').textContent = 'Add Gallery Item';
    document.getElementById('galleryImagePreview').style.display = 'none';
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
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
}

function closeAllModals() {
    document.querySelectorAll('.modal.open').forEach(m => m.classList.remove('open'));
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
    if (!dateString) return '';
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

function renderPagination(containerId, totalPages, currentPage, loadFn) {
    const container = document.getElementById(containerId);
    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '';
    for (let i = 1; i <= totalPages; i++) {
        html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="${loadFn.name}(${i})">${i}</button>`;
    }
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

function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash-fill';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye-fill';
    }
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast toast-' + type + ' show';
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Close modals on overlay click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal') && e.target.classList.contains('open')) {
        e.target.classList.remove('open');
        document.body.style.overflow = '';
    }
});
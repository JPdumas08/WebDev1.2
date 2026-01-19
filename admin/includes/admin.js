/**
 * Jeweluxe Admin Dashboard JavaScript
 * Helper functions and utilities for admin dashboard
 */

// Utility: Debounce function for performance
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Utility: Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(amount);
}

// Utility: Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
}

// Toggle sidebar on mobile
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
}

// Toggle notifications panel
function toggleNotifications() {
    const notificationPanel = document.getElementById('notificationPanel');
    if (notificationPanel) {
        notificationPanel.classList.toggle('open');
    }
}

// Show success alert
function showSuccess(message) {
    const alertHtml = `
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i>
            <div>
                <strong>Success!</strong> ${message}
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    insertAlert(alertHtml);
}

// Show error alert
function showError(message) {
    const alertHtml = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong>Error!</strong> ${message}
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    insertAlert(alertHtml);
}

// Show warning alert
function showWarning(message) {
    const alertHtml = `
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Warning!</strong> ${message}
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    insertAlert(alertHtml);
}

// Show info alert
function showInfo(message) {
    const alertHtml = `
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Info:</strong> ${message}
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    insertAlert(alertHtml);
}

// Insert alert at the top of main content
function insertAlert(alertHtml) {
    const mainContent = document.querySelector('.admin-main');
    if (mainContent) {
        const alertContainer = document.createElement('div');
        alertContainer.innerHTML = alertHtml;
        mainContent.insertBefore(alertContainer.firstElementChild, mainContent.firstChild);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            const alert = mainContent.querySelector('.alert');
            if (alert) {
                alert.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
    }
}

// Confirm delete action
function confirmDelete(message = 'Are you sure you want to delete this item? This action cannot be undone.') {
    return confirm(message);
}

// Validate email
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Validate phone number
function validatePhone(phone) {
    const phoneRegex = /^[\d\s\-\+\(\)]{10,}$/;
    return phoneRegex.test(phone);
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Initialize tooltips (Bootstrap)
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Initialize popovers (Bootstrap)
function initializePopovers() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

// Document ready handler
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    initializeTooltips();
    initializePopovers();
    
    // Close sidebar when clicking on nav links on mobile
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function() {
            const sidebar = document.getElementById('adminSidebar');
            if (sidebar && window.innerWidth <= 768) {
                sidebar.classList.remove('open');
            }
        });
    });
    
    // Handle responsive sidebar toggle button
    const updateSidebarToggle = () => {
        const toggleBtn = document.getElementById('sidebarToggle');
        if (toggleBtn) {
            toggleBtn.style.display = window.innerWidth <= 768 ? 'flex' : 'none';
        }
    };
    
    window.addEventListener('resize', updateSidebarToggle);
    updateSidebarToggle();
    
    // Add fade out animation for alerts
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }
    `;
    document.head.appendChild(style);
});

// Custom Modal for Admin
const AdminModal = {
    show: function(title, message, onConfirm, onCancel) {
        // Remove existing modal if any
        const existingModal = document.getElementById('adminCustomModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Create modal HTML
        const modalHtml = `
            <div class="modal fade" id="adminCustomModal" tabindex="-1" style="z-index: 9999;">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                        <div class="modal-header" style="border-bottom: 1px solid #e0e0e0; padding: 1.5rem;">
                            <h5 class="modal-title" style="font-weight: 600; color: #2c3e50;">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="padding: 1.5rem; color: #555;">
                            ${message}
                        </div>
                        <div class="modal-footer" style="border-top: 1px solid #e0e0e0; padding: 1rem 1.5rem;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="adminModalConfirm">OK</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Get modal element
        const modalElement = document.getElementById('adminCustomModal');
        const modal = new bootstrap.Modal(modalElement);

        // Handle confirm button
        document.getElementById('adminModalConfirm').addEventListener('click', function() {
            modal.hide();
            if (onConfirm) onConfirm();
        });

        // Handle cancel
        modalElement.addEventListener('hidden.bs.modal', function() {
            modalElement.remove();
            if (onCancel) onCancel();
        });

        // Show modal
        modal.show();
    },
    
    success: function(message) {
        showSuccess(message);
    },
    
    error: function(message) {
        showError(message);
    }
};

// Export functions for global use
window.AdminHelpers = {
    debounce,
    formatCurrency,
    formatDate,
    toggleSidebar,
    toggleNotifications,
    showSuccess,
    showError,
    showWarning,
    showInfo,
    confirmDelete,
    validateEmail,
    validatePhone,
    formatFileSize,
    initializeTooltips,
    initializePopovers
};

window.AdminModal = AdminModal;

// UI helpers: toast notifications and confirm modal.
(function(global) {
  const ToastNotification = {
    show(message, type = 'success', duration = 3000) {
      let toastContainer = document.getElementById('customToastContainer');
      if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'customToastContainer';
        toastContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
        document.body.appendChild(toastContainer);
      }

      const toast = document.createElement('div');
      const typeClass = {
        success: 'bg-success',
        error: 'bg-danger',
        warning: 'bg-warning',
        info: 'bg-info'
      }[type] || 'bg-info';

      toast.innerHTML = `
        <div class="alert alert-dismissible fade show ${typeClass} text-white mb-3" role="alert" style="min-width: 300px;">
          <div>
            <strong>${type.charAt(0).toUpperCase() + type.slice(1)}!</strong> ${message}
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
      `;

      toastContainer.appendChild(toast.firstElementChild);

      if (duration > 0) {
        setTimeout(() => {
          const dismissBtn = toast.querySelector('[data-bs-dismiss="alert"]');
          if (dismissBtn) dismissBtn.click();
        }, duration);
      }
    },

    success(message) { this.show(message, 'success'); },
    error(message) { this.show(message, 'error'); },
    warning(message) { this.show(message, 'warning'); },
    info(message) { this.show(message, 'info'); }
  };

  const ConfirmModal = {
    show(title, message, onConfirm, onCancel) {
      let modal = document.getElementById('customConfirmModal');
      if (!modal) {
        modal = document.createElement('div');
        modal.id = 'customConfirmModal';
        modal.className = 'modal fade';
        modal.tabIndex = -1;
        modal.setAttribute('aria-hidden', 'true');
        document.body.appendChild(modal);
      }

      modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">${title}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              ${message}
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-primary" id="confirmBtn">Confirm</button>
            </div>
          </div>
        </div>
      `;

      const bsModal = new bootstrap.Modal(modal);
      const confirmBtn = modal.querySelector('#confirmBtn');

      confirmBtn.addEventListener('click', () => {
        bsModal.hide();
        if (onConfirm) onConfirm();
      });

      modal.addEventListener('hidden.bs.modal', () => {
        if (onCancel) onCancel();
      });

      bsModal.show();
      return false;
    }
  };

  global.ToastNotification = ToastNotification;
  global.ConfirmModal = ConfirmModal;
})(window);

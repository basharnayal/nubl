/**
 * Recipient providers list: Flowbite modal for provider menu.
 */
import { Modal } from 'flowbite';

const MODAL_ID = 'provider-menu-modal';

function initProviderMenuModal() {
  const modalEl = document.getElementById(MODAL_ID);
  const titleEl = document.getElementById('provider-menu-modal-title');
  const bodyEl = document.getElementById('provider-menu-modal-body');

  if (!modalEl || !titleEl || !bodyEl) return;

  const modal = new Modal(modalEl);

  // Manually bind close: our Modal is created after initFlowbite, so data-modal-hide was not bound
  const closeBtn = document.querySelector(`[data-modal-hide="${MODAL_ID}"]`);
  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      modal.hide();
      document.body.classList.remove('overflow-hidden');
    });
  }

  function openMenuForCard(card) {
    const url = card.getAttribute('data-menu-url');
    const name = card.getAttribute('data-provider-name') || 'Menu';

    if (!url) return;

    titleEl.textContent = name;
    bodyEl.innerHTML = '<p class="text-gray-500">Loading…</p>';

    document.body.classList.add('overflow-hidden');
    modal.show();

    fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
    })
      .then((r) => r.text())
      .then((html) => {
        bodyEl.innerHTML = html;
      })
      .catch(() => {
        bodyEl.innerHTML =
          '<p class="text-red-500">Failed to load menu. Please try again.</p>';
      });
  }

  document.querySelectorAll('.provider-card').forEach((card) => {
    card.addEventListener('click', () => openMenuForCard(card));
    card.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        openMenuForCard(card);
      }
    });
  });
}

// Module loads after DOM is ready; run init when script executes
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initProviderMenuModal);
} else {
  initProviderMenuModal();
}

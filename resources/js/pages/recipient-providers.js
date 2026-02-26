/**
 * Recipient providers list: Alpine-based modal for provider menu.
 */
function providerMenuModal() {
  return {
    show: false,
    title: 'Menu',
    body: '<p class="text-slate-500 dark:text-navy-300">Loading…</p>',
    loading: true,

    open(menuUrl, providerName) {
      if (!menuUrl) return;
      this.title = providerName || 'Menu';
      this.body = '<p class="text-slate-500 dark:text-navy-300">Loading…</p>';
      this.loading = true;
      this.show = true;
      document.body.classList.add('overflow-hidden');

      fetch(menuUrl, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
      })
        .then((r) => r.text())
        .then((html) => {
          this.body = html;
          this.loading = false;
        })
        .catch(() => {
          this.body = '<p class="text-error">Failed to load menu. Please try again.</p>';
          this.loading = false;
        });
    },

    close() {
      this.show = false;
      document.body.classList.remove('overflow-hidden');
    },
  };
}

// Register Alpine component - runs before Alpine.start()
if (window.Alpine) {
  Alpine.data('providerMenuModal', providerMenuModal);
}

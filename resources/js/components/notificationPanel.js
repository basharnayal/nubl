import usePopper from './usePopper';

export default function notificationPanel() {
  const base = usePopper({ placement: 'bottom-end', offset: 12 });

  return {
    ...base,
    notifications: [],
    unreadCount: 0,
    loading: true,

    async fetchNotifications() {
      try {
        const res = await fetch('/notifications', {
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
        });
        if (res.ok) {
          const data = await res.json();
          this.notifications = data.notifications || [];
          this.unreadCount = data.unread_count ?? 0;
        }
      } catch (e) {
        this.notifications = [];
        this.unreadCount = 0;
      } finally {
        this.loading = false;
      }
    },

    async markAllRead() {
      try {
        await fetch('/notifications/read-all', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
        });
        this.notifications.forEach((n) => (n.read_at = new Date().toISOString()));
        this.unreadCount = 0;
      } catch (_) {}
    },
  };
}

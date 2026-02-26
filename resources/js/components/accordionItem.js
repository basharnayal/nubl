export default (id) => ({
  acc_id: id,
  get expanded() {
    const store = Alpine.store('sidebarAccordion') || {};
    return store.expandedItem === this.acc_id;
  },
  set expanded(val) {
    if (!Alpine.store('sidebarAccordion')) {
      Alpine.store('sidebarAccordion', { expandedItem: null });
    }
    Alpine.store('sidebarAccordion').expandedItem = val ? this.acc_id : null;
  }
});

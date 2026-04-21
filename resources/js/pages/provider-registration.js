/**
 * Provider registration form — step validation.
 * Edit STEP_CONFIG to add/remove required fields per step.
 */
const STEP_CONFIG = {
  1: {
    requiredInputs: ['full_name_ar', 'full_name_en', 'phone_number', 'email', 'business_name_ar', 'business_name_en', 'unified_number', 'address_ar', 'address_en', 'city'],
    checkboxGroups: [{ name: 'business_category[]', min: 1, msgKey: 'business_category' }],
    customValidators: ['phone', 'region'],
  },
  2: {
    requiredInputs: ['daily_capacity', 'estimated_preparation_order_time', 'adoption_support'],
    checkboxGroups: [{ name: 'service_type[]', min: 1, msgKey: 'service_type' }],
    customValidators: ['operatingHours'],
  },
  3: {
    requiredInputs: ['bank_name', 'iban', 'account_holder_name'],
  },
};

/** Mirrors App\Support\PhoneHelper::nationalMobileDigits (digits-only input paths). */
function saudiNationalMobileDigits(raw) {
  let s = String(raw || '').replace(/\D/g, '');
  if (s.startsWith('00966')) s = s.slice(5);
  else if (s.startsWith('966')) s = s.slice(3);
  s = s.replace(/^0+/, '');
  return s;
}

const SAUDI_PHONE_REGEX = /^[125]\d{8}$/;

function providerForm(initialStep, weekdays) {
  return {
    step: initialStep,
    weekdays: weekdays || [],

    init() {
      this.$nextTick(() => this.normalizePhone());
    },

    /** Same idea as register.blade.php: digits, max local length, then strip leading 0 for 05XXXXXXXX */
    normalizePhoneInput(el) {
      if (!el) return;
      let v = (el.value || '').replace(/\D/g, '').slice(0, 10);
      if (v.length === 10 && v.startsWith('0')) v = v.replace(/^0+/, '');
      el.value = v;
    },

    normalizePhone() {
      const el = document.getElementById('phone_number');
      this.normalizePhoneInput(el);
    },

    validateAndNext() {
      const result = this.validateStep(this.step);
      if (!result.ok) {
        this.showError(result.msg);
        return;
      }
      this.hideError();
      this.step++;
    },

    hideError() {
      const banner = document.getElementById('provider-validation-error');
      if (banner) {
        banner.classList.add('hidden');
        banner.textContent = '';
      }
    },

    validateStep(stepNum) {
      const config = STEP_CONFIG[stepNum];
      if (!config) return { ok: true };

      // Required text/select inputs
      for (const name of config.requiredInputs || []) {
        const el = document.querySelector(`[name="${name}"]`);
        if (el && !(el.value || '').trim()) {
          return { ok: false, msg: this.msg('fill_required') };
        }
      }

      // Checkbox groups (min selections)
      for (const group of config.checkboxGroups || []) {
        const checked = document.querySelectorAll(`input[name="${group.name}"]:checked`);
        if (checked.length < (group.min || 1)) {
          return { ok: false, msg: this.msg(group.msgKey) };
        }
      }

      // Custom validators
      for (const key of config.customValidators || []) {
        const r = this.runCustomValidator(key);
        if (!r.ok) return r;
      }

      return { ok: true };
    },

    runCustomValidator(key) {
      if (key === 'phone') {
        const phone = saudiNationalMobileDigits(document.getElementById('phone_number')?.value || '');
        if (phone.length !== 9 || !SAUDI_PHONE_REGEX.test(phone)) {
          return { ok: false, msg: this.msg('phone_invalid') };
        }
      }
      if (key === 'region') {
        const region = document.querySelector('[name="region"]');
        if (region && !(region.value || '').trim()) return { ok: false, msg: this.msg('region') };
      }
      if (key === 'operatingHours') {
        for (const d of this.weekdays) {
          const closed = document.querySelector(`input[name="operating_hours[${d}][closed]"]`)?.checked;
          if (!closed) {
            const open = document.querySelector(`input[name="operating_hours[${d}][open]"]`)?.value;
            const close = document.querySelector(`input[name="operating_hours[${d}][close]"]`)?.value;
            if (!open || !close) return { ok: false, msg: this.msg('operating_hours') };
          }
        }
      }
      return { ok: true };
    },

    showError(msg) {
      const banner = document.getElementById('provider-validation-error');
      if (banner) {
        banner.textContent = msg;
        banner.classList.remove('hidden');
        banner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      } else {
        alert(msg);
      }
    },

    msg(key) {
      const messages = {
        fill_required: 'Please fill all required fields in this step.',
        business_category: 'Please select at least one business category.',
        service_type: 'Please select at least one service type.',
        phone_invalid: 'Phone must be a valid Saudi number (9 digits, e.g. 512345678).',
        region: 'Please select region.',
        operating_hours: 'Please set opening and closing time for each open day, or mark as closed.',
      };
      return window.__providerFormMessages?.[key] ?? messages[key] ?? key;
    },
  };
}

// Register with Alpine and expose for Blade
window.providerForm = providerForm;

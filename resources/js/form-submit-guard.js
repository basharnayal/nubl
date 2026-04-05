/**
 * Single global guard: block duplicate POST submissions (rapid clicks).
 * The first successful POST often calls session()->regenerate(); a second in-flight
 * POST still carries the old _token and fails with 419 TokenMismatchException.
 *
 * Opt out: <form data-allow-double-submit="true" ...>
 */
export function initFormSubmitGuard() {
  document.addEventListener(
    'submit',
    (e) => {
      const form = e.target;
      if (!(form instanceof HTMLFormElement)) {
        return;
      }
      if ((form.getAttribute('method') || 'get').toLowerCase() !== 'post') {
        return;
      }
      if (form.dataset.allowDoubleSubmit === 'true') {
        return;
      }
      if (form.dataset.csrfSubmitGuard === '1') {
        e.preventDefault();
        return;
      }
      form.dataset.csrfSubmitGuard = '1';

      const submitter =
        form.querySelector('button[type="submit"]:not([disabled])') ||
        form.querySelector('input[type="submit"]:not([disabled])');
      if (submitter) {
        submitter.disabled = true;
      }
    },
    true
  );
}

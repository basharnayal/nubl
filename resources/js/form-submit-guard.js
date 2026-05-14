/**
 * Single global guard: block duplicate POST submissions (rapid clicks).
 * The first successful POST often calls session()->regenerate(); a second in-flight
 * POST still carries the old _token and fails with 419 TokenMismatchException.
 *
 * Runs in the bubbling phase (false) so inline handlers (e.g. Alpine.js validation)
 * fire first. If any handler called event.preventDefault() the guard skips entirely —
 * this prevents the submit button from being permanently disabled when client-side
 * validation blocks the submission.
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
      // If a prior handler (e.g. Alpine validation) already cancelled the submission,
      // do not mark the form as submitted or disable the button.
      if (e.defaultPrevented) {
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
    false  // bubbling phase: fires after inline/Alpine handlers on the form element
  );
}

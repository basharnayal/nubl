<x-app-layout title="{{ __('Scan QR Code') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-medium text-slate-800 dark:text-navy-50">
                {{ __('Scan QR Code') }}
            </h2>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:gap-6">
            <div class="card p-4 sm:p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-medium text-slate-700 dark:text-navy-100">
                        {{ __('Camera Scanner') }}
                    </h3>
                </div>

                <div id="qr-reader"
                    class="w-full bg-slate-100 dark:bg-navy-900 rounded-lg overflow-hidden border border-slate-200 dark:border-navy-600">
                </div>

                <div id="scan-result" class="mt-4 hidden p-4 rounded-lg bg-success/10 text-success">
                    <p class="font-bold">{{ __('Scan successful. Redirecting...') }}</p>
                </div>
                <div id="scan-error" class="mt-4 hidden p-4 rounded-lg bg-error/10 text-error">
                    <p class="font-bold error-msg"></p>
                </div>
            </div>

            <div class="card p-4 sm:p-5 h-fit">
                <h3 class="text-base font-medium text-slate-700 dark:text-navy-100 mb-4">
                    {{ __('Manual Entry') }}
                </h3>
                <form id="manual-redeem-form" class="space-y-4">
                    <label class="block">
                        <span class="font-medium text-slate-600 dark:text-navy-100">{{ __('Token Code') }}</span>
                        <input type="text" name="token" id="manual_token"
                            class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                            placeholder="{{ __('Enter code manually') }}" required />
                    </label>
                    <button type="submit"
                        class="btn w-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90"
                        id="btn-redeem">
                        {{ __('Redeem Order') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let isRedeeming = false;
            let lastToken = null;

            function processRedemption(token) {
                if (isRedeeming) return;
                isRedeeming = true;
                lastToken = token;

                const errorBox = document.getElementById('scan-error');
                const successBox = document.getElementById('scan-result');
                errorBox.classList.add('hidden');
                successBox.classList.add('hidden');

                const btn = document.getElementById('btn-redeem');
                const originalText = btn.innerText;
                btn.innerText = '{{ __("Processing...") }}';
                btn.disabled = true;

                fetch('{{ route("provider.qr.redeem") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ token: token })
                })
                    .then(response => response.json().then(data => ({ status: response.status, body: data })))
                    .then(({ status, body }) => {
                        if (status === 200) {
                            successBox.classList.remove('hidden');
                            window.location.href = body.redirect_url;
                        } else {
                            errorBox.querySelector('.error-msg').innerText = body.error || '{{ __("An error occurred.") }}';
                            errorBox.classList.remove('hidden');
                            isRedeeming = false;
                            btn.innerText = originalText;
                            btn.disabled = false;

                            // Resume scanner after failure so they can try again or scan a different code
                            if (typeof html5QrcodeScanner !== 'undefined') {
                                setTimeout(() => {
                                    // By intentionally NOT clearing lastToken here, the camera 
                                    // will safely ignore the identical physical QR code if it 
                                    // remains on screen, preventing the API flooding loop.
                                    html5QrcodeScanner.resume();
                                }, 2000);
                            }
                        }
                    })
                    .catch(err => {
                        errorBox.querySelector('.error-msg').innerText = '{{ __("Network error.") }}';
                        errorBox.classList.remove('hidden');
                        isRedeeming = false;
                        btn.innerText = originalText;
                        btn.disabled = false;

                        if (typeof html5QrcodeScanner !== 'undefined') {
                            setTimeout(() => {
                                html5QrcodeScanner.resume();
                            }, 2000);
                        }
                    });
            }

            // HTML5 QR Code Scanner
            function onScanSuccess(decodedText, decodedResult) {
                if (isRedeeming) return;
                if (decodedText === lastToken) return;

                if (typeof html5QrcodeScanner !== 'undefined') {
                    html5QrcodeScanner.pause();
                }
                processRedemption(decodedText);
            }

            try {
                let html5QrcodeScanner = new Html5QrcodeScanner(
                    "qr-reader",
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 250 },
                        videoConstraints: { facingMode: "environment" }
                    },
                    /* verbose= */ false
                );
                html5QrcodeScanner.render(onScanSuccess, function (error) {
                    // Ignore regular scan failures
                });
            } catch (e) {
                console.error("Camera scanner failed to load", e);
                const errorBox = document.getElementById('scan-error');
                errorBox.querySelector('.error-msg').innerText = 'Camera scanner failed to load. Please use manual entry.';
                errorBox.classList.remove('hidden');
            }

            // Manual Submit
            document.getElementById('manual-redeem-form').addEventListener('submit', function (e) {
                e.preventDefault();
                const token = document.getElementById('manual_token').value;
                if (token.trim() !== '') {
                    processRedemption(token.trim());
                }
            });
        });
    </script>
</x-app-layout>
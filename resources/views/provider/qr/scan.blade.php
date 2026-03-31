<x-app-layout title="{{ __('Scan QR Code') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="mb-8 space-y-2 lg:mb-10">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ __('Scan QR Code') }}
            </h1>
            <p class="max-w-2xl text-sm leading-snug text-slate-600 dark:text-navy-300">
                {{ __('Provider QR scan page subtitle') }}
            </p>
        </div>

        <div class="grid grid-cols-1 items-stretch gap-6 lg:grid-cols-2 lg:gap-8">
            {{-- Camera: primary emphasis (desktop: left column in RTL = inline-end) --}}
            <div
                class="order-2 flex flex-col rounded-xl border border-primary/20 bg-gradient-to-b from-primary/[0.06] to-white p-4 shadow-sm dark:border-accent/30 dark:from-accent/10 dark:to-navy-800/40 sm:p-6 lg:order-2">
                <div class="mb-4 flex items-center gap-3">
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/15 text-primary dark:bg-accent/20 dark:text-accent-light">
                        <i class="fa-solid fa-camera text-lg" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Camera Scanner') }}</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-navy-400">{{ __('Point camera at the customer QR code') }}</p>
                    </div>
                </div>

                <div id="qr-reader"
                    class="qr-scan-viewport w-full min-h-[min(22rem,55vh)] flex-1 overflow-hidden rounded-xl border-2 border-dashed border-primary/25 bg-slate-950/5 dark:border-accent/30 dark:bg-navy-900/50">
                </div>

                <div id="scan-result" class="mt-4 hidden rounded-xl border border-success/30 bg-success/10 p-4 text-success dark:border-success/40 dark:bg-success/15"
                    role="status" aria-live="polite">
                    <p class="flex items-start gap-2 font-bold">
                        <i class="fa-solid fa-circle-check mt-0.5 shrink-0" aria-hidden="true"></i>
                        <span>{{ __('Scan successful. Redirecting...') }}</span>
                    </p>
                </div>
                <div id="scan-error" class="mt-4 hidden rounded-xl border border-error/35 bg-error/10 p-4 text-error dark:border-error/40 dark:bg-error/15"
                    role="alert" aria-live="assertive">
                    <p class="error-msg flex items-start gap-2 font-bold"></p>
                </div>
            </div>

            {{-- Manual entry (desktop: first column in RTL = right / inline-start) --}}
            <div
                class="order-1 flex flex-col rounded-xl border border-primary/20 bg-gradient-to-b from-primary/[0.07] to-white p-4 shadow-sm dark:border-accent/30 dark:from-accent/[0.12] dark:to-navy-800/50 sm:p-6 lg:order-1">
                <div class="mb-4 flex items-center gap-3">
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/15 text-primary dark:bg-accent/20 dark:text-accent-light">
                        <i class="fa-solid fa-keyboard text-lg" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Manual Entry') }}</h2>
                        <p class="mt-0.5 text-xs text-slate-600 dark:text-navy-300">{{ __('Enter the code if the camera is unavailable') }}</p>
                    </div>
                </div>

                <form id="manual-redeem-form" class="mt-2 flex flex-1 flex-col space-y-4">
                    <label class="block rounded-xl border border-primary/15 bg-white/80 p-3 shadow-sm dark:border-accent/20 dark:bg-navy-900/40 sm:p-4">
                        <span class="text-sm font-semibold text-slate-800 dark:text-navy-50">{{ __('Token Code') }}</span>
                        <input type="text" name="token" id="manual_token"
                            dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
                            class="form-input mt-2 w-full rounded-xl border border-primary/20 bg-white px-4 py-3 text-slate-800 shadow-sm placeholder:text-slate-400/80 hover:border-primary/35 focus:border-primary focus:ring-2 focus:ring-primary/30 dark:border-accent/30 dark:bg-navy-800/80 dark:text-navy-100 dark:placeholder:text-navy-400 dark:hover:border-accent/50 dark:focus:border-accent dark:focus:ring-accent/35"
                            placeholder="{{ __('Enter code manually') }}" required autocomplete="off" inputmode="text" />
                    </label>
                    <button type="submit"
                        class="btn mt-auto inline-flex min-h-[3rem] w-full items-center justify-center rounded-xl bg-primary px-4 py-3 text-base font-bold text-white shadow-md transition hover:bg-primary-focus focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus-visible:ring-accent dark:focus-visible:ring-offset-navy-900 dark:active:bg-accent/90"
                        id="btn-redeem">
                        {{ __('Redeem Order') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <style>
        /* html5-qrcode default UI — NUBL primary / dark accent */
        #qr-reader .html5-qrcode-element,
        #qr-reader button {
            border-radius: 0.75rem;
            font-weight: 600;
        }

        #qr-reader button.html5-qrcode-element,
        #qr-reader button {
            border: 1px solid color-mix(in srgb, var(--color-primary) 42%, transparent);
            background: color-mix(in srgb, var(--color-primary) 11%, transparent);
            color: var(--color-primary);
            padding: 0.5rem 1rem;
            transition: background 0.15s ease, border-color 0.15s ease;
        }

        .dark #qr-reader button.html5-qrcode-element,
        .dark #qr-reader button {
            border-color: color-mix(in srgb, var(--color-accent) 45%, transparent);
            background: color-mix(in srgb, var(--color-accent) 14%, transparent);
            color: var(--color-accent-light);
        }

        #qr-reader button.html5-qrcode-element:hover,
        #qr-reader button:hover {
            background: color-mix(in srgb, var(--color-primary) 18%, transparent);
        }

        .dark #qr-reader button.html5-qrcode-element:hover,
        .dark #qr-reader button:hover {
            background: color-mix(in srgb, var(--color-accent) 22%, transparent);
        }

        #qr-reader #html5-qrcode-anchor-scan-type-change {
            font-size: 0.875rem;
        }

        #qr-reader video {
            border-radius: 0.75rem;
        }
    </style>

    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const i18n = {
                processing: @json(__('Processing...')),
                errorGeneric: @json(__('An error occurred.')),
                networkError: @json(__('Network error.')),
                cameraLoadFailed: @json(__('Camera scanner failed to load. Please use manual entry.')),
                qrUi: {
                    'Start Scanning': @json(__('Start scanning')),
                    'Stop Scanning': @json(__('Stop scanning')),
                    'Request Camera Permissions': @json(__('Request camera permission')),
                    'Scan an Image File': @json(__('Scan image file')),
                }
            };

            function translateQrScannerUi() {
                const root = document.getElementById('qr-reader');
                if (!root) return;
                root.querySelectorAll('button, a[role="button"], span').forEach(function (el) {
                    const raw = (el.textContent || '').trim();
                    if (i18n.qrUi[raw]) {
                        el.textContent = i18n.qrUi[raw];
                    }
                });
            }

            let isRedeeming = false;
            let lastToken = null;
            let html5QrcodeScanner = null;

            function processRedemption(token) {
                if (isRedeeming) return;
                isRedeeming = true;
                lastToken = token;

                const errorBox = document.getElementById('scan-error');
                const successBox = document.getElementById('scan-result');
                const errorMsg = errorBox.querySelector('.error-msg');
                errorBox.classList.add('hidden');
                successBox.classList.add('hidden');

                const btn = document.getElementById('btn-redeem');
                const originalText = btn.innerText;
                btn.innerText = i18n.processing;
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
                            const msg = body.error || i18n.errorGeneric;
                            errorMsg.innerHTML = '<i class="fa-solid fa-circle-exclamation mt-0.5 shrink-0" aria-hidden="true"></i><span></span>';
                            errorMsg.querySelector('span').textContent = msg;
                            errorBox.classList.remove('hidden');
                            isRedeeming = false;
                            btn.innerText = originalText;
                            btn.disabled = false;

                            if (html5QrcodeScanner) {
                                setTimeout(() => {
                                    html5QrcodeScanner.resume();
                                }, 2000);
                            }
                        }
                    })
                    .catch(() => {
                        errorMsg.innerHTML = '<i class="fa-solid fa-circle-exclamation mt-0.5 shrink-0" aria-hidden="true"></i><span></span>';
                        errorMsg.querySelector('span').textContent = i18n.networkError;
                        errorBox.classList.remove('hidden');
                        isRedeeming = false;
                        btn.innerText = originalText;
                        btn.disabled = false;

                        if (html5QrcodeScanner) {
                            setTimeout(() => {
                                html5QrcodeScanner.resume();
                            }, 2000);
                        }
                    });
            }

            function onScanSuccess(decodedText, decodedResult) {
                if (isRedeeming) return;
                if (decodedText === lastToken) return;

                if (html5QrcodeScanner) {
                    html5QrcodeScanner.pause();
                }
                processRedemption(decodedText);
            }

            try {
                html5QrcodeScanner = new Html5QrcodeScanner(
                    "qr-reader",
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 250 },
                        videoConstraints: { facingMode: "environment" }
                    },
                    false
                );
                html5QrcodeScanner.render(onScanSuccess, function () {});
                [100, 400, 1000, 2000].forEach(function (ms) {
                    setTimeout(translateQrScannerUi, ms);
                });
            } catch (e) {
                console.error("Camera scanner failed to load", e);
                const errorBox = document.getElementById('scan-error');
                const errorMsg = errorBox.querySelector('.error-msg');
                errorMsg.innerHTML = '<i class="fa-solid fa-circle-exclamation mt-0.5 shrink-0" aria-hidden="true"></i><span></span>';
                errorMsg.querySelector('span').textContent = i18n.cameraLoadFailed;
                errorBox.classList.remove('hidden');
            }

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

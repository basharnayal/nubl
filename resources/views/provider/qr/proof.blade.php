<x-app-layout title="{{ __('Upload Proof') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="mb-6 space-y-2 lg:mb-8">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                {{ __('Upload Proof of Fulfillment') }}
            </h1>
            <p class="max-w-2xl text-sm leading-relaxed text-slate-600 dark:text-navy-300" dir="auto">
                {{ __('Please upload a photo of the receipt or a photo of the food being handed over to complete the fulfillment of this request.') }}
            </p>
        </div>

        <div class="card p-4 sm:p-5 max-w-2xl mx-auto">
            <div class="border-b border-slate-200 pb-4 mb-4 dark:border-navy-600">
                <h3 class="font-bold text-lg text-slate-700 dark:text-navy-100">{{ __('Order Details') }}</h3>
                <p class="text-sm text-slate-500 dark:text-navy-400">
                    {{ __('Request #') }}{{ $redemption->request->id }} &bull; {{ __('Recipient') }}:
                    {{ $redemption->request->recipient->name }}
                </p>
                <p class="text-sm font-semibold text-primary mt-2">
                    {{ __('Reserved Amount') }}: {{ number_format($redemption->request->reserved_amount, 2) }}
                    {{ __('SAR') }}
                </p>
            </div>

            <div class="p-4 sm:p-6">
                <div
                    class="mb-6 flex gap-3 rounded-xl border border-primary/20 bg-primary/10 p-4 dark:border-accent/25 dark:bg-accent/10">
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/15 text-primary dark:bg-accent/20 dark:text-accent-light">
                        <i class="fa-solid fa-circle-info text-lg" aria-hidden="true"></i>
                    </span>
                    <p class="text-sm leading-relaxed text-slate-700 dark:text-navy-100" dir="auto">
                        <strong class="text-primary dark:text-accent-light">{{ __('Required Action') }}:</strong>
                        {{ __('Choose one option below: upload a file from your device or capture a photo with your camera.') }}
                    </p>
                </div>

                <form action="{{ route('provider.proof.store', $redemption->id) }}" method="POST"
                    enctype="multipart/form-data" class="space-y-6" x-data="proofUpload()" x-on:submit="validateBeforeSubmit($event)">
                    @csrf

                    <div class="space-y-6">
                        {{-- Option 1: File Upload --}}
                        <div
                            class="rounded-xl border border-primary/20 bg-gradient-to-b from-primary/[0.06] to-white p-4 shadow-sm dark:border-accent/30 dark:from-accent/[0.08] dark:to-navy-800/40 sm:p-5">
                            <div class="mb-4 flex items-center gap-3">
                                <span
                                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/15 text-primary dark:bg-accent/20 dark:text-accent-light">
                                    <i class="fa-solid fa-file-arrow-up text-lg" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Option 1: Upload File') }}</h3>
                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-navy-400">{{ __('JPG, PNG, WEBP, or PDF up to 5MB') }}</p>
                                </div>
                            </div>
                            <label class="block rounded-xl border border-primary/15 bg-white/90 p-3 shadow-sm dark:border-accent/20 dark:bg-navy-900/50 sm:p-4">
                                <div class="text-sm font-semibold text-slate-800 dark:text-navy-50">{{ __('Choose file') }}</div>
                                <div class="mt-2" dir="ltr">
                                    <input type="file" name="proof_file" accept=".jpg,.jpeg,.png,.webp,.pdf" x-ref="fileInput"
                                        x-on:change="onFileChange()"
                                        class="form-input w-full rounded-lg border border-primary/20 bg-white px-3 py-2 text-sm text-slate-800 file:me-3 file:rounded-md file:border-0 file:bg-primary/15 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary hover:border-primary/40 focus:border-primary focus:ring-2 focus:ring-primary/25 dark:border-accent/30 dark:bg-navy-800/80 dark:text-navy-100 dark:file:bg-accent/20 dark:file:text-accent-light dark:hover:border-accent/50 dark:focus:border-accent dark:focus:ring-accent/30" />
                                </div>
                                <small class="mt-2 block text-xs leading-relaxed text-slate-500 dark:text-navy-400" dir="auto">
                                    {{ __('Allowed formats: JPG, PNG, WEBP, PDF. Max size: 5MB.') }}
                                </small>
                                @error('proof_file')
                                    <span class="mt-2 block text-xs text-error">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="h-px flex-1 bg-gradient-to-l from-transparent via-primary/20 to-primary/20 dark:via-accent/25 dark:to-accent/25">
                            </div>
                            <span
                                class="shrink-0 rounded-full border border-primary/20 bg-primary/5 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-primary dark:border-accent/30 dark:bg-accent/10 dark:text-accent-light">{{ __('OR') }}</span>
                            <div class="h-px flex-1 bg-gradient-to-r from-transparent via-primary/20 to-primary/20 dark:via-accent/25 dark:to-accent/25">
                            </div>
                        </div>

                        {{-- Option 2: Camera Capture --}}
                        <div
                            class="rounded-xl border border-primary/20 bg-gradient-to-b from-primary/[0.06] to-white p-4 shadow-sm dark:border-accent/30 dark:from-accent/[0.1] dark:to-navy-800/40 sm:p-5">
                            <div class="mb-4 flex items-center gap-3">
                                <span
                                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/15 text-primary dark:bg-accent/20 dark:text-accent-light">
                                    <i class="fa-solid fa-camera text-lg" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Option 2: Capture Photo') }}</h3>
                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-navy-400">{{ __('Use your device camera to take proof') }}</p>
                                </div>
                            </div>

                            <div x-show="!photoCaptured" class="space-y-3">
                                <div x-show="!cameraActive" class="flex flex-wrap gap-2">
                                    <button type="button" x-on:click="startCamera()"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-primary-focus focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:bg-accent dark:hover:bg-accent-focus dark:focus-visible:ring-accent dark:focus-visible:ring-offset-navy-900">
                                        <i class="fa-solid fa-video" aria-hidden="true"></i>
                                        {{ __('Start Camera') }}
                                    </button>
                                </div>
                                <div x-show="cameraActive" class="space-y-3">
                                    <div
                                        class="overflow-hidden rounded-xl border-2 border-dashed border-primary/30 bg-slate-950/5 dark:border-accent/35 dark:bg-navy-900/50">
                                        <video id="camera-preview" x-ref="video" autoplay playsinline
                                            class="aspect-video w-full min-h-[min(16rem,45vh)] object-cover"></video>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" x-on:click="capturePhoto()"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-primary-focus focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:bg-accent dark:hover:bg-accent-focus dark:focus-visible:ring-accent dark:focus-visible:ring-offset-navy-900">
                                            <i class="fa-solid fa-camera" aria-hidden="true"></i>
                                            {{ __('Capture Photo') }}
                                        </button>
                                        <button type="button" x-on:click="stopCamera()"
                                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-navy-500 dark:bg-navy-700 dark:text-navy-100 dark:hover:bg-navy-600">
                                            {{ __('Cancel') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div x-show="photoCaptured" class="space-y-3" x-cloak>
                                <div
                                    class="overflow-hidden rounded-xl border border-primary/25 bg-slate-950/5 dark:border-accent/30 dark:bg-navy-900/40">
                                    <img x-ref="previewImg" src="" alt="{{ __('Captured') }}"
                                        class="max-h-64 w-full object-contain sm:max-h-80" />
                                </div>
                                <button type="button" x-on:click="retakePhoto()"
                                    class="inline-flex items-center gap-2 text-sm font-semibold text-primary hover:text-primary-focus dark:text-accent-light dark:hover:text-accent">
                                    <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                    {{ __('Retake photo') }}
                                </button>
                            </div>

                            <input type="hidden" name="proof_photo_base64" x-model="photoBase64" />
                            @error('proof_photo_base64')
                                <span class="mt-2 block text-xs text-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <button type="submit"
                        class="btn inline-flex min-h-[3rem] w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-base font-bold text-white shadow-md transition hover:bg-primary-focus focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus-visible:ring-accent dark:focus-visible:ring-offset-navy-900 dark:active:bg-accent/90">
                        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                        {{ __('Submit Proof and Fulfill Order') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function proofUpload() {
            return {
                cameraActive: false,
                photoCaptured: false,
                photoBase64: '',
                stream: null,
                fileSelected: false,

                onFileChange() {
                    const input = this.$refs.fileInput;
                    this.fileSelected = input.files && input.files.length > 0;
                    if (this.fileSelected && this.photoCaptured) {
                        this.retakePhoto();
                        this.stopCamera();
                    }
                },

                async startCamera() {
                    if (this.$refs.fileInput) {
                        this.$refs.fileInput.value = '';
                        this.fileSelected = false;
                    }

                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                        this.$refs.video.srcObject = this.stream;
                        this.cameraActive = true;
                    } catch (err) {
                        alert('{{ __("Camera access is required to capture the proof photo. Please allow camera permission.") }}');
                        console.error(err);
                    }
                },

                stopCamera() {
                    if (this.stream) {
                        this.stream.getTracks().forEach(track => track.stop());
                        this.stream = null;
                    }
                    this.cameraActive = false;
                },

                capturePhoto() {
                    const video = this.$refs.video;
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0);
                    this.photoBase64 = canvas.toDataURL('image/jpeg', 0.85);
                    this.photoCaptured = true;
                    this.$refs.previewImg.src = this.photoBase64;
                    this.stopCamera();
                },

                retakePhoto() {
                    this.photoCaptured = false;
                    this.photoBase64 = '';
                    this.startCamera();
                },

                validateBeforeSubmit(event) {
                    if (!this.fileSelected && !this.photoBase64) {
                        event.preventDefault();
                        alert('{{ __("You must provide proof by either uploading a file or capturing a photo.") }}');
                        return false;
                    }
                }
            };
        }
    </script>
</x-app-layout>

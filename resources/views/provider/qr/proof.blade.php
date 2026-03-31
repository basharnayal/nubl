<x-app-layout title="{{ __('Upload Proof') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-medium text-slate-800 dark:text-navy-50">
                {{ __('Upload Proof of Fulfillment') }}
            </h2>
        </div>

        <div class="card p-4 sm:p-5 max-w-2xl mx-auto">
            <div class="border-b border-slate-200 pb-4 mb-4 dark:border-navy-600">
                <h3 class="font-bold text-lg text-slate-700 dark:text-navy-100">{{ __('Order Details') }}</h3>
                <p class="text-sm text-slate-500 dark:text-navy-400">
                    {{ __('Request #') }}{{ $redemption->request->id }} &bull; {{ __('Reference') }}:
                    <span class="font-mono">{{ \App\Support\PseudonymousRequestId::make($redemption->request->id) }}</span>
                </p>
                <p class="text-sm font-semibold text-primary mt-2">
                    {{ __('Reserved Amount') }}: {{ number_format($redemption->request->reserved_amount, 2) }}
                    {{ __('SAR') }}
                </p>
            </div>

            <div class="bg-info/10 p-4 rounded-lg mb-6 border border-info/20 text-info">
                <p class="text-sm">
                    <strong>{{ __('Required Action') }}:</strong>
                    {{ __('Please upload a photo of the receipt or a photo of the food being handed over to complete the fulfillment of this request.') }}
                </p>
            </div>

            <form action="{{ route('provider.proof.store', $redemption->id) }}" method="POST"
                enctype="multipart/form-data" class="space-y-6" x-data="proofUpload()" x-on:submit="validateBeforeSubmit($event)">
                @csrf
                
                <div class="space-y-4">
                    {{-- Option 1: File Upload --}}
                    <div class="rounded-lg border border-slate-300 p-4 dark:border-navy-500">
                        <label class="block">
                            <span class="font-medium text-slate-600 dark:text-navy-100">{{ __('Option 1: Upload File') }}</span>
                            <input type="file" name="proof_file" accept=".jpg,.jpeg,.png,.webp,.pdf" x-ref="fileInput" x-on:change="onFileChange()"
                                class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                                 />
                            <small class="text-xs text-slate-400 mt-1 block">
                                {{ __('Allowed formats: JPG, PNG, WEBP, PDF. Max size: 5MB.') }}
                            </small>
                            @error('proof_file')
                                <span class="text-xs text-error mt-1">{{ $message }}</span>
                            @enderror
                        </label>
                    </div>

                    <div class="flex items-center">
                        <div class="h-px flex-1 bg-slate-200 dark:bg-navy-500"></div>
                        <span class="px-4 text-sm text-slate-400 dark:text-navy-300">{{ __('OR') }}</span>
                        <div class="h-px flex-1 bg-slate-200 dark:bg-navy-500"></div>
                    </div>

                    {{-- Option 2: Camera Capture --}}
                    <div class="rounded-lg border border-primary/20 bg-primary/10 p-4 dark:border-accent/20 dark:bg-accent/10">
                        <span class="font-medium text-slate-600 dark:text-navy-100 block mb-2">{{ __('Option 2: Capture Photo') }}</span>
                        
                        <div x-show="!photoCaptured" class="space-y-3">
                            <div x-show="!cameraActive" class="flex gap-2">
                                <button type="button" x-on:click="startCamera()"
                                    class="text-white bg-primary hover:bg-primary-focus focus:ring-4 focus:ring-primary/20 dark:bg-accent dark:hover:bg-accent-focus dark:focus:ring-accent/20 font-medium rounded-lg text-sm px-5 py-2.5 transition">
                                    {{ __('Start Camera') }}
                                </button>
                            </div>
                            <div x-show="cameraActive" class="space-y-3">
                                <video id="camera-preview" x-ref="video" autoplay playsinline class="w-full rounded-lg border border-slate-300 dark:border-navy-500"></video>
                                <div class="flex gap-2">
                                    <button type="button" x-on:click="capturePhoto()"
                                        class="text-white bg-primary hover:bg-primary-focus focus:ring-4 focus:ring-primary/20 dark:bg-accent dark:hover:bg-accent-focus dark:focus:ring-accent/20 font-medium rounded-lg text-sm px-5 py-2.5 transition">
                                        {{ __('Capture Photo') }}
                                    </button>
                                    <button type="button" x-on:click="stopCamera()"
                                        class="text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 dark:text-navy-100 dark:bg-navy-700 dark:border-navy-500 dark:hover:bg-navy-600 font-medium rounded-lg text-sm px-5 py-2.5 transition">
                                        {{ __('Cancel') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div x-show="photoCaptured" class="space-y-2" x-cloak>
                            <img x-ref="previewImg" src="" alt="{{ __('Captured') }}" class="max-h-40 rounded-lg border border-slate-300 dark:border-navy-500" />
                            <button type="button" x-on:click="retakePhoto()"
                                class="text-primary hover:text-primary-focus dark:text-accent-light dark:hover:text-accent font-medium text-sm block">
                                {{ __('Retake photo') }}
                            </button>
                        </div>
        
                        <input type="hidden" name="proof_photo_base64" x-model="photoBase64" />
                        @error('proof_photo_base64')
                            <span class="text-xs text-error mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <button type="submit"
                    class="btn w-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                    {{ __('Submit Proof and Fulfill Order') }}
                </button>
            </form>
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
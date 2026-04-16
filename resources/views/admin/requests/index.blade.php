<x-app-layout title="{{ __('Admin Request Queue') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6">
            <div>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-base font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                        {{ __('Admin Request Queue') }}
                    </h2>
                    <div class="flex items-center gap-2 rounded-lg bg-slate-150 px-4 py-2 dark:bg-navy-600">
                        <span
                            class="text-xs uppercase tracking-wider text-slate-500 dark:text-navy-300">{{ __('City Fund Balance') }}</span>
                        <span
                            class="font-bold text-slate-800 dark:text-navy-100">{{ number_format($cityFundBalance ?? 0, 2) }}
                            {{ __('SAR') }}</span>
                    </div>
                </div>

                <div class="card mt-3">
                    @if($requests->isEmpty())
                        <div class="px-6 py-12 text-center text-slate-500 dark:text-navy-300">
                            {{ __('No pending requests requiring admin approval.') }}
                        </div>
                    @else
                        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                            <table class="is-hoverable w-full text-left">
                                <thead>
                                    <tr>
                                        <th
                                            class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                            {{ __('ID') }}</th>
                                        <th
                                            class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                            {{ __('Provider') }}</th>
                                        <th
                                            class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                            {{ __('Recipient') }}</th>
                                        <th
                                            class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                            {{ __('Amount') }}</th>
                                        <th
                                            class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                            {{ __('Date') }}</th>
                                        <th
                                            class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                            {{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($requests as $request)
                                        <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                                <p class="font-medium text-primary dark:text-accent-light">#{{ $request->id }}
                                                </p>
                                            </td>
                                            <td class="px-4 py-3 sm:px-5">
                                                <span
                                                    class="font-medium text-slate-700 dark:text-navy-100">{{ $request->provider->name }}</span>
                                            </td>
                                            <td class="px-4 py-3 sm:px-5">
                                                <span
                                                    class="font-medium text-slate-700 dark:text-navy-100">{{ $request->recipient->name }}</span>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                                <p class="text-sm-plus font-medium text-slate-700 dark:text-navy-100">
                                                    {{ number_format($request->reserved_amount, 2) }} {{ __('SAR') }}</p>
                                            </td>
                                            <td class="px-4 py-3 sm:px-5">
                                                <div class="text-xs text-slate-500 dark:text-navy-300">
                                                    {{ $request->created_at->diffForHumans() }}</div>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                                <button type="button"
                                                    onclick="openReviewModal({{ json_encode($request) }}, {{ json_encode($request->items) }})"
                                                    class="btn size-8 rounded-full p-0 text-primary hover:bg-primary/10 focus:bg-primary/10 dark:text-accent dark:hover:bg-accent/10 dark:focus:bg-accent/10">
                                                    {{ __('Review') }}
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 px-4 sm:px-5">
                            {{ $requests->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Review Modal --}}
    <div id="review-modal" tabindex="-1" aria-hidden="true"
        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-black/50 backdrop-blur-sm flex">
        <div class="relative p-4 w-full max-w-2xl max-h-full">
            <div
                class="relative rounded-lg border border-slate-150 bg-white shadow-soft dark:border-navy-600 dark:bg-navy-750">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-navy-600">
                    <h3 class="text-base font-semibold text-slate-800 dark:text-navy-100" id="modal-title">
                        {{ __('Review Request') }} #<span id="modal-req-id"></span>
                    </h3>
                    <button type="button" onclick="closeReviewModal()"
                        class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20">
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                        </svg>
                    </button>
                </div>
                <div class="p-4 md:p-5 space-y-4">
                    <div id="modal-items-list"
                        class="space-y-2 rounded-lg bg-slate-100 p-4 max-h-60 overflow-y-auto dark:bg-navy-600/50">
                        {{-- Items injected via JS --}}
                    </div>

                    <div
                        class="flex justify-between items-center font-bold text-lg border-t border-slate-200 pt-4 dark:border-navy-600">
                        <span class="text-slate-700 dark:text-navy-100">{{ __('Total Amount') }}:</span>
                        <span id="modal-total" class="text-primary dark:text-accent-light"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <form id="approve-form" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="action" value="approve">
                            <button type="submit"
                                class="btn w-full bg-success text-white hover:bg-success-focus focus:bg-success-focus active:bg-success-focus/90 dark:bg-success dark:hover:bg-success-focus dark:focus:bg-success-focus">
                                {{ __('Approve (City Fund)') }}
                            </button>
                        </form>

                        <button type="button" onclick="toggleRejectForm()"
                            class="btn w-full bg-error text-white hover:bg-error-focus focus:bg-error-focus active:bg-error-focus/90 dark:bg-error dark:hover:bg-error-focus dark:focus:bg-error-focus">
                            {{ __('Reject') }}
                        </button>
                    </div>

                    <form id="reject-form" method="POST"
                        class="hidden mt-4 rounded-lg border border-error/30 bg-error/10 p-4 dark:bg-error/15 dark:border-error/20">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="action" value="reject">

                        <label
                            class="mb-2 block text-sm font-medium text-slate-700 dark:text-navy-100">{{ __('Rejection Reason') }}</label>
                        <select name="rejection_reason_code" required class="form-select form-select-lineone">
                            <option value="">{{ __('Select Reason...') }}</option>
                            <option value="Insufficient Funds">{{ __('Insufficient Funds') }}</option>
                            <option value="Policy Violation">{{ __('Policy Violation') }}</option>
                            <option value="Duplicate Request">{{ __('Duplicate Request') }}</option>
                            <option value="Other">{{ __('Other') }}</option>
                        </select>
                        <textarea name="rejection_reason_note" rows="2" class="form-textarea form-textarea-lineone mt-2"
                            placeholder="{{ __('Notes (optional)') }}"></textarea>

                        <button type="submit"
                            class="btn mt-3 w-full bg-error text-white hover:bg-error-focus dark:bg-error dark:hover:bg-error-focus">
                            {{ __('Confirm Rejection') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openReviewModal(request, items) {
            document.getElementById('modal-req-id').textContent = request.id;
            document.getElementById('modal-total').textContent = parseFloat(request.reserved_amount).toFixed(2) + ' {{ __('SAR') }}';

            const list = document.getElementById('modal-items-list');
            list.innerHTML = '';
            items.forEach(item => {
                const div = document.createElement('div');
                div.className = 'flex justify-between border-b border-slate-200 pb-2 last:border-0 dark:border-navy-500';
                const itemName = item.menu_item ? item.menu_item.name : 'Item #' + item.menu_item_id;
                div.innerHTML = `
                    <div>
                        <span class="block font-medium text-slate-700 dark:text-navy-100">${itemName}</span>
                        <span class="text-xs text-slate-500 dark:text-navy-300">Qty: ${item.quantity}</span>
                    </div>
                    <span class="font-bold text-slate-700 dark:text-navy-100">${(item.price_snapshot * item.quantity).toFixed(2)}</span>
                `;
                list.appendChild(div);
            });

            const url = "{{ route('admin.requests.update', ':id') }}".replace(':id', request.id);
            document.getElementById('approve-form').action = url;
            document.getElementById('reject-form').action = url;

            document.getElementById('reject-form').classList.add('hidden');
            document.getElementById('review-modal').classList.remove('hidden');
        }

        function closeReviewModal() {
            document.getElementById('review-modal').classList.add('hidden');
        }

        function toggleRejectForm() {
            document.getElementById('reject-form').classList.toggle('hidden');
        }
    </script>
</x-app-layout>
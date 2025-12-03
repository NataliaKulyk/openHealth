@if(session('error') || session('success') || session('status'))
    <div class="alert-message flex fixed top-[1.5rem] w-auto z-[100000] right-2"
         wire:key="{{ time() }}"
         x-data="message"
         x-show="showAlertMessage"
    >
        @session('error')
            <div role="alert"
                 class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400"
            >
                <span class="font-medium">{{ session('error') }}</span>
            </div>
        @endsession

        @session('success')
            <div role="alert"
                 class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400"
            >
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        @endsession

        @session('status')
            <x-message.successes>
                <x-slot name="status">{{ session('status') }}</x-slot>
            </x-message.successes>
        @endsession
    </div>
@endif
<script>
    document.addEventListener('alpine:init', () => {
        Livewire.hook('commit.prepare', () => {
            Alpine.data('message', () => ({
                showAlertMessage: true,
                init() {
                    setTimeout(() => this.showAlertMessage = false, 30000)
                }
            }))
        })
    });
</script>

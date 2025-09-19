<div x-data="{ open: false }" class="relative">
    <button @click="open = !open"
            x-transition
            type="button"
            aria-label="Notifications"
            class="cursor-pointer p-2 mr-1 text-gray-500 rounded-lg hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
    >
        @icon('bell', 'w-6 h-6')
    </button>

    {{-- List of notifications --}}
    <div x-show="open"
         x-cloak
         @click.away="open = false"
         class="absolute right-0 mt-2 w-80 max-w-sm bg-white rounded-xl shadow-lg z-50 overflow-hidden dark:bg-gray-700 dark:divide-gray-600"
    >
        <div
            class="block py-2 px-4 text-base font-medium text-center text-gray-700 bg-gray-50 dark:bg-gray-600 dark:text-gray-300"
        >
            {{ __('forms.notifications') }}
        </div>
        <ul>
            @forelse($notifications as $notification)
                <li wire:key="notification-{{ $notification->id }}"
                    wire:transition
                    class="flex items-center justify-between px-4 py-2 border-b last:border-b-0 border-gray-100 dark:border-gray-600"
                >

                    {{-- Message --}}
                    <div>
                        <span class="text-gray-900 dark:text-white">{{ $notification->data['message'] ?? '' }}</span>
                        <small class="block text-xs text-gray-400">
                            {{ $notification->created_at->diffForHumans() }}
                        </small>
                    </div>

                    {{-- Remove button --}}
                    <button wire:click="markAsRead('{{ $notification->id }}')"
                            type="button"
                            class="cursor-pointer ml-2 text-xs text-blue-600 hover:underline"
                    >
                        {{ __('forms.mark_as_read') }}
                    </button>
                </li>
            @empty
                <li class="px-4 py-2 text-center text-gray-400">{{ __('forms.empty') }}</li>
            @endforelse
        </ul>
    </div>
</div>

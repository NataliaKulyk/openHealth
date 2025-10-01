@if ($paginator->hasPages())
    {{-- Головна навігаційна обгортка --}}
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex justify-center">

        {{-- Обгортка для елементів. Використовуємо -space-x-px для ефекту накладання меж --}}
        <div class="flex items-center -space-x-px text-base">

            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                {{-- Стилі для неактивної кнопки (Disabled) --}}
                <span class="flex items-center justify-center px-4 h-10 ms-0 leading-tight text-gray-500 bg-white border border-e-0 border-gray-300 rounded-s-lg cursor-default dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400">
                <span class="sr-only">Previous</span>
                <svg class="w-3 h-3 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 1 1 5l4 4"/>
                </svg>
            </span>
            @else
                {{-- Стилі для активної кнопки (Enabled) --}}
                <button wire:click="previousPage" wire:loading.attr="disabled" rel="prev" class="flex items-center justify-center px-4 h-10 ms-0 leading-tight text-gray-500 bg-white border border-e-0 border-gray-300 rounded-s-lg hover:bg-gray-100 hover:text-gray-700 focus:z-10 focus:outline-none focus:ring ring-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white transition ease-in-out duration-150">
                    <span class="sr-only">Previous</span>
                    <svg class="w-3 h-3 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 1 1 5l4 4"/>
                    </svg>
                </button>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    {{-- Стилі для роздільника "..." --}}
                    {{-- Забезпечуємо відсутність hover-ефекту для роздільника --}}
                    <span class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 cursor-default dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            {{-- Стилі для активної (поточної) сторінки --}}
                            {{-- На активній сторінці hover-ефект має бути відсутній або мінімальний --}}
                            <span aria-current="page" class="z-10 flex items-center justify-center px-4 h-10 leading-tight text-blue-600 border border-blue-300 bg-blue-50 cursor-default dark:border-gray-700 dark:bg-gray-700 dark:text-white">
                            {{ $page }}
                        </span>
                        @else
                            {{-- Стилі для неактивної сторінки --}}
                            {{-- Тут застосовані класи dark:hover:bg-gray-700 dark:hover:text-white --}}
                            <button wire:click="gotoPage({{ $page }})" class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 focus:z-10 focus:outline-none focus:ring ring-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white transition ease-in-out duration-150">
                                {{ $page }}
                            </button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                {{-- Стилі для активної кнопки "Наступна" --}}
                <button wire:click="nextPage" wire:loading.attr="disabled" rel="next" class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg hover:bg-gray-100 hover:text-gray-700 focus:z-10 focus:outline-none focus:ring ring-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white transition ease-in-out duration-150">
                    <span class="sr-only">Next</span>
                    <svg class="w-3 h-3 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                    </svg>
                </button>
            @else
                {{-- Стилі для неактивної кнопки "Наступна" (Disabled) --}}
                <span class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg cursor-default dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400">
                <span class="sr-only">Next</span>
                <svg class="w-3 h-3 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                </svg>
            </span>
            @endif
        </div>
    </nav>

@endif

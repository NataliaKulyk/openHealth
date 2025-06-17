<div class="bg-white dark:bg-gray-800 min-h-screen">
<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="table-nav">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white mt-6">{{ __('Ліцензії') }}</h1>
        <a href="{{ route('license.create') }}" class="default-button">
            + Нова ліцензія
        </a>
    </div>

    <div class="table-container bg-white dark:bg-800 shadow-md border border-gray-200 dark:border-gray-700 rounded-lg">
    <table class="table-base">
            <thead class="table-header">
            <tr class="border-b border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                <th class="th-input text-gray-900 dark:text-gray-100">Тип ліцензії</th>
                <th class="th-input text-gray-900 dark:text-gray-100">Дата початку дії</th>
                <th class="th-input text-gray-900 dark:text-gray-100">Дата завершення дії</th>
                <th class="th-input text-gray-900 dark:text-gray-100">Напрям діяльності</th>
                <th class="th-input text-gray-900 dark:text-gray-100">Вид ліцензії</th>
                <th class="th-input text-gray-900 dark:text-gray-100">Дія</th>
            </tr>
            </thead>
            <tbody>
            @foreach($licensesPagination as $license)
                <tr>
                    <td class="td-input table-cell-primary">{{ $license->type }}</td>
                    <td class="td-input">{{ $license->start_date ?? '—' }}</td>
                    <td class="td-input">{{ $license->end_date ?? '—' }}</td>
                    <td class="td-input">{{ $license->what_licensed ?? '—' }}</td>
                    <td class="td-input">
                        @if($license->is_primary)
                            <span class="badge-green">Основна</span>
                        @else
                            <span class="badge-yellow">Додаткова</span>
                        @endif
                    </td>
                    <td class="td-input text-center">
                        <a href="{{ route('license.show', $license->id) }}" class="text-gray-500 hover:text-gray-800 dark:hover:text-white"></a>
                        <a href="{{ route('license.form', $license->id) }}" class="ml-2 text-gray-500 hover:text-gray-800 dark:hover:text-white"></a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        <x-pagination :pagination="$licensesPagination" />
    </div>
</div>
</div>

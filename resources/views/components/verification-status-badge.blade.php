@props([
    'status' => null,
    'prefix' => '',
])

@php
    $badgeClass = '';
    $text = '';

    switch ($status) {
        case 'VERIFIED':
            $badgeClass = 'badge-green';
            $text = __('general.verified');
            break;

        case 'NOT_VERIFIED':
            $badgeClass = 'badge-red';
            $text = __('general.not_verified');
            break;

        case 'VERIFICATION_NEEDED':
            $badgeClass = 'badge-yellow';
            $text = __('general.verification_needed');
            break;

        case 'VERIFICATION_NOT_NEEDED':
            $badgeClass = 'badge-gray';
            $text = __('general.verification_not_needed');
            break;

        default:
            // If the status is null or unknown, we do not show anything
            if ($status === null) {
                $text = '-';
            } else {
                // We show an unknown status, if there is one
                $badgeClass = 'badge-gray';
                $text = $status;
            }
            break;
    }
@endphp

{{-- If the text is not "-", show the badge --}}
@if ($text !== '-')
    <span class="{{ $badgeClass }} whitespace-nowrap">
      {{-- Show the prefix if it is --}}
        @if($prefix)
            <span class="opacity-75">{{ $prefix }}</span>
        @endif
        {{ $text }}
    </span>
@else
    {{-- Otherwise just a hyphen --}}
    <span>-</span>
@endif

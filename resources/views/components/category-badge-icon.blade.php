@props(['bucket' => 'needs', 'categoryName' => ''])

@php
    $lower = mb_strtolower((string) $categoryName);
    $palette = match($bucket) {
        'income' => ['bg' => '#D8F5E5', 'accent' => '#45C486', 'ink' => '#114A2D'],
        'wants' => ['bg' => '#FFEAA0', 'accent' => '#FFD54F', 'ink' => '#263238'],
        'emergency' => ['bg' => '#EAE2FF', 'accent' => '#9B7EDE', 'ink' => '#3A1D75'],
        default => ['bg' => '#D6ECFA', 'accent' => '#407BFF', 'ink' => '#163C94'],
    };

    if (str_contains($lower, 'makan') || str_contains($lower, 'kuliner') || str_contains($lower, 'kopi')) {
        $palette = ['bg' => '#FEEBC8', 'accent' => '#F6AD55', 'ink' => '#744210'];
        $shape = 'food';
    } elseif (str_contains($lower, 'transport') || str_contains($lower, 'bensin')) {
        $palette = ['bg' => '#D6ECFA', 'accent' => '#407BFF', 'ink' => '#163C94'];
        $shape = 'car';
    } elseif (str_contains($lower, 'sehat') || str_contains($lower, 'medis')) {
        $palette = ['bg' => '#FFE2DE', 'accent' => '#FF6B6B', 'ink' => '#8C1D1D'];
        $shape = 'heart';
    } elseif (str_contains($lower, 'tagihan') || str_contains($lower, 'listrik') || str_contains($lower, 'didik')) {
        $palette = ['bg' => '#D8F5E5', 'accent' => '#45C486', 'ink' => '#114A2D'];
        $shape = 'receipt';
    } elseif ($bucket === 'income' || str_contains($lower, 'gaji') || str_contains($lower, 'freelance') || str_contains($lower, 'bonus')) {
        $palette = ['bg' => '#D8F5E5', 'accent' => '#45C486', 'ink' => '#114A2D'];
        $shape = 'wallet';
    } elseif ($bucket === 'emergency') {
        $shape = 'shield';
    } elseif ($bucket === 'wants') {
        $palette = ['bg' => '#FDE2E4', 'accent' => '#FF6B6B', 'ink' => '#8C1D1D'];
        $shape = 'bag';
    } else {
        $shape = 'brick';
    }
@endphp

<div class="w-10 h-10 rounded-xl border-[1.5px] border-[#263238] flex items-center justify-center shrink-0"
     style="background-color: {{ $palette['bg'] }}; color: {{ $palette['ink'] }};" aria-hidden="true">
    @switch($shape)
        @case('food')
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                <path d="M4 13H20C20 17.4 16.4 20 12 20C7.6 20 4 17.4 4 13Z" fill="{{ $palette['accent'] }}" stroke="#263238" stroke-width="1.8"/>
                <path d="M8 9C8 7 9.5 6 9.5 4.5M12 9C12 7 13.5 6 13.5 4.5M16 9C16 7 17.5 6 17.5 4.5" stroke="#263238" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            @break
        @case('car')
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                <path d="M5 11L7 6H17L19 11V17H5V11Z" fill="{{ $palette['accent'] }}" stroke="#263238" stroke-width="1.8" stroke-linejoin="round"/>
                <circle cx="8" cy="17" r="2" fill="#FFFFFF" stroke="#263238" stroke-width="1.6"/>
                <circle cx="16" cy="17" r="2" fill="#FFFFFF" stroke="#263238" stroke-width="1.6"/>
            </svg>
            @break
        @case('heart')
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                <path d="M12 20S4 14.8 4 9.5C4 6.8 6.1 5 8.5 5C10.1 5 11.4 5.9 12 7.1C12.6 5.9 13.9 5 15.5 5C17.9 5 20 6.8 20 9.5C20 14.8 12 20 12 20Z" fill="{{ $palette['accent'] }}" stroke="#263238" stroke-width="1.8"/>
            </svg>
            @break
        @case('receipt')
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                <rect x="6" y="4" width="12" height="16" rx="2" fill="#FFFFFF" stroke="#263238" stroke-width="1.8"/>
                <path d="M9 9H15M9 13H15M9 17H12" stroke="{{ $palette['ink'] }}" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            @break
        @case('wallet')
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="6" width="18" height="13" rx="3" fill="{{ $palette['accent'] }}" stroke="#263238" stroke-width="1.8"/>
                <rect x="14" y="10" width="7" height="5" rx="1.5" fill="#FFFFFF" stroke="#263238" stroke-width="1.6"/>
            </svg>
            @break
        @case('shield')
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                <path d="M12 3L19 6V12C19 16.5 16 19.8 12 21C8 19.8 5 16.5 5 12V6L12 3Z" fill="{{ $palette['accent'] }}" stroke="#263238" stroke-width="1.8"/>
                <path d="M9.5 12L11.2 13.8L14.8 10" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            @break
        @case('bag')
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                <path d="M6 8H18L19 20H5L6 8Z" fill="{{ $palette['accent'] }}" stroke="#263238" stroke-width="1.8" stroke-linejoin="round"/>
                <path d="M9 8V6C9 4.3 10.3 3 12 3C13.7 3 15 4.3 15 6V8" stroke="#263238" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            @break
        @default
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                <rect x="4" y="8" width="16" height="11" rx="3" fill="{{ $palette['accent'] }}" stroke="#263238" stroke-width="1.8"/>
                <rect x="7" y="5" width="4" height="3" rx="1" fill="#FFD54F" stroke="#263238" stroke-width="1.5"/>
                <rect x="13" y="5" width="4" height="3" rx="1" fill="#45C486" stroke="#263238" stroke-width="1.5"/>
            </svg>
    @endswitch
</div>

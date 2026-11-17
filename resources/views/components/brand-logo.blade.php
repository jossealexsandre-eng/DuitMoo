@props(['size' => 'md'])

@php
    $boxClass = match($size) {
        'sm' => 'w-9 h-9 rounded-2xl border-2',
        'lg' => 'w-14 h-14 rounded-3xl border-2',
        default => 'w-11 h-11 rounded-2xl border-2',
    };
@endphp

<div {{ $attributes->merge(['class' => "{$boxClass} bg-[#FFFDF9] border-[#263238] shadow-[2px_2px_0_#263238] flex items-center justify-center shrink-0 relative overflow-hidden transition-transform hover:scale-105"]) }}>
    <!-- Adorable DuitMoo Cow Mascot Logo -->
    <svg viewBox="0 0 48 48" class="w-[88%] h-[88%]" fill="none" aria-hidden="true">
        <!-- Ears -->
        <ellipse cx="9" cy="18" rx="6" ry="4" transform="rotate(-25 9 18)" fill="#FFD54F" stroke="#263238" stroke-width="1.8"/>
        <ellipse cx="9" cy="18" rx="3.5" ry="2" transform="rotate(-25 9 18)" fill="#FFA8A8"/>
        <ellipse cx="39" cy="18" rx="6" ry="4" transform="rotate(25 39 18)" fill="#FFD54F" stroke="#263238" stroke-width="1.8"/>
        <ellipse cx="39" cy="18" rx="3.5" ry="2" transform="rotate(25 39 18)" fill="#FFA8A8"/>

        <!-- Little Horns -->
        <path d="M15 13C13 8 17 6 19 11" fill="#FFFFFF" stroke="#263238" stroke-width="1.8" stroke-linecap="round"/>
        <path d="M33 13C35 8 31 6 29 11" fill="#FFFFFF" stroke="#263238" stroke-width="1.8" stroke-linecap="round"/>

        <!-- Head / Body -->
        <rect x="10" y="11" width="28" height="30" rx="14" fill="#FFFFFF" stroke="#263238" stroke-width="2"/>
        
        <!-- Green DuitMoo Spot on Forehead -->
        <path d="M26 11C31 12 36 15 37 20C32 21 28 17 26 11Z" fill="#45C486" stroke="#263238" stroke-width="1.4"/>

        <!-- Big Soulful Eyes -->
        <ellipse cx="18" cy="23" rx="3.5" ry="4.5" fill="#263238"/>
        <circle cx="17" cy="21.5" r="1.5" fill="#FFFFFF"/>
        <ellipse cx="30" cy="23" rx="3.5" ry="4.5" fill="#263238"/>
        <circle cx="29" cy="21.5" r="1.5" fill="#FFFFFF"/>

        <!-- Rosy Cheeks -->
        <circle cx="13" cy="28" r="2.2" fill="#FF8A8A" fill-opacity="0.45"/>
        <circle cx="35" cy="28" r="2.2" fill="#FF8A8A" fill-opacity="0.45"/>

        <!-- Muzzle -->
        <rect x="15" y="27" width="18" height="11" rx="5.5" fill="#FFF2CC" stroke="#263238" stroke-width="1.8"/>
        <circle cx="21" cy="31" r="1.1" fill="#263238"/>
        <circle cx="27" cy="31" r="1.1" fill="#263238"/>
        <path d="M22.5 34C23.5 35 24.5 35 25.5 34" stroke="#263238" stroke-width="1.2" stroke-linecap="round"/>
    </svg>
</div>

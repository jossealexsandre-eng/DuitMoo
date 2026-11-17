@props(['variant' => 'peek', 'class' => 'w-14 h-10'])

@if($variant === 'eyes')
    <!-- Subtle playful brick eyes (inspired by tactile character cards) -->
    <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 44 20" fill="none" aria-hidden="true">
        <ellipse cx="14" cy="10" rx="7" ry="8" fill="#FFFFFF" stroke="#263238" stroke-width="2"/>
        <ellipse cx="30" cy="10" rx="7" ry="8" fill="#FFFFFF" stroke="#263238" stroke-width="2"/>
        <circle cx="15" cy="10.5" r="3.2" fill="#263238"/>
        <circle cx="31" cy="10.5" r="3.2" fill="#263238"/>
        <circle cx="16.2" cy="9.2" r="1.1" fill="#FFFFFF"/>
        <circle cx="32.2" cy="9.2" r="1.1" fill="#FFFFFF"/>
    </svg>
@elseif($variant === 'locked-eyes')
    <!-- Playful Cute Locked Eyes (Shaped like cute little golden padlocks with eyes) -->
    <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 44 20" fill="none" aria-hidden="true">
        <!-- Left Padlock Eye -->
        <path d="M11 8V6C11 4.5 12.3 3 14 3C15.7 3 17 4.5 17 6V8" stroke="#263238" stroke-width="1.8" stroke-linecap="round"/>
        <rect x="9" y="8" width="10" height="9" rx="3" fill="#FFD54F" stroke="#263238" stroke-width="1.8"/>
        <circle cx="14" cy="12" r="1.2" fill="#263238"/>
        <path d="M14 13.2V15" stroke="#263238" stroke-width="1.2" stroke-linecap="round"/>

        <!-- Right Padlock Eye -->
        <path d="M27 8V6C27 4.5 28.3 3 30 3C31.7 3 33 4.5 33 6V8" stroke="#263238" stroke-width="1.8" stroke-linecap="round"/>
        <rect x="25" y="8" width="10" height="9" rx="3" fill="#FFD54F" stroke="#263238" stroke-width="1.8"/>
        <circle cx="30" cy="12" r="1.2" fill="#263238"/>
        <path d="M30 13.2V15" stroke="#263238" stroke-width="1.2" stroke-linecap="round"/>
    </svg>
@elseif($variant === 'hero')
    <!-- Full Expressive DuitMoo Character (Inspired by Petal in Aptivo & Cozy Cat in Record) -->
    <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 160 160" fill="none" aria-hidden="true">
        <!-- Soft Warm Shadow -->
        <ellipse cx="80" cy="148" rx="55" ry="8" fill="#263238" fill-opacity="0.12"/>
        
        <!-- Ears -->
        <ellipse cx="32" cy="54" rx="18" ry="11" transform="rotate(-25 32 54)" fill="#FFD54F" stroke="#263238" stroke-width="3"/>
        <ellipse cx="32" cy="54" rx="10" ry="6" transform="rotate(-25 32 54)" fill="#FFE8A3"/>
        <ellipse cx="128" cy="54" rx="18" ry="11" transform="rotate(25 128 54)" fill="#FFD54F" stroke="#263238" stroke-width="3"/>
        <ellipse cx="128" cy="54" rx="10" ry="6" transform="rotate(25 128 54)" fill="#FFE8A3"/>
        
        <!-- Little Horns -->
        <path d="M50 36C45 22 55 18 60 28" fill="#FFFFFF" stroke="#263238" stroke-width="3" stroke-linecap="round"/>
        <path d="M110 36C115 22 105 18 100 28" fill="#FFFFFF" stroke="#263238" stroke-width="3" stroke-linecap="round"/>
        
        <!-- Head & Body (Chubby Cozy Shape) -->
        <rect x="36" y="30" width="88" height="96" rx="44" fill="#FFFFFF" stroke="#263238" stroke-width="3.5"/>
        
        <!-- Organic Green Toy-Brick Patch on Forehead -->
        <path d="M84 31C98 33 108 42 110 55C98 56 88 48 84 31Z" fill="#45C486" stroke="#263238" stroke-width="2.5"/>
        
        <!-- Big Soulful Expressive Eyes (Aptivo Style) -->
        <ellipse cx="60" cy="68" rx="11" ry="13" fill="#263238"/>
        <circle cx="57" cy="63" r="4.5" fill="#FFFFFF"/>
        <circle cx="63" cy="73" r="2" fill="#FFFFFF"/>
        
        <ellipse cx="100" cy="68" rx="11" ry="13" fill="#263238"/>
        <circle cx="97" cy="63" r="4.5" fill="#FFFFFF"/>
        <circle cx="103" cy="73" r="2" fill="#FFFFFF"/>
        
        <!-- Rosy Cheeks -->
        <ellipse cx="44" cy="82" rx="7" ry="4.5" fill="#FF8A8A" fill-opacity="0.45"/>
        <ellipse cx="116" cy="82" rx="7" ry="4.5" fill="#FF8A8A" fill-opacity="0.45"/>
        
        <!-- Soft Muzzle & Smile -->
        <rect x="52" y="78" width="56" height="30" rx="15" fill="#FFF2CC" stroke="#263238" stroke-width="3"/>
        <!-- Cute Nostrils -->
        <circle cx="68" cy="89" r="3" fill="#263238"/>
        <circle cx="92" cy="89" r="3" fill="#263238"/>
        <!-- Happy Mouth -->
        <path d="M74 96C77 99 83 99 86 96" stroke="#263238" stroke-width="2.8" stroke-linecap="round"/>
        
        <!-- Hugging Golden Coin Brick with Hands -->
        <g transform="translate(48, 106)">
            <!-- Golden Coin -->
            <rect x="8" y="0" width="48" height="34" rx="12" fill="#FFD54F" stroke="#263238" stroke-width="3"/>
            <circle cx="32" cy="17" r="10" fill="#FFEAA0" stroke="#263238" stroke-width="2"/>
            <path d="M30 11V23M27 14H37M27 20H37" stroke="#263238" stroke-width="2" stroke-linecap="round"/>
            <!-- Left Hand Paws -->
            <ellipse cx="6" cy="17" rx="8" ry="6" fill="#FFFFFF" stroke="#263238" stroke-width="3"/>
            <!-- Right Hand Paws -->
            <ellipse cx="58" cy="17" rx="8" ry="6" fill="#FFFFFF" stroke="#263238" stroke-width="3"/>
        </g>
    </svg>
@else
    <!-- DuitMoo original friendly coin-cow mascot peeking playfully over ledger cards -->
    <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 88 52" fill="none" aria-hidden="true">
        <!-- Ears -->
        <ellipse cx="16" cy="24" rx="9" ry="6" transform="rotate(-18 16 24)" fill="#FFD54F" stroke="#263238" stroke-width="2.5"/>
        <ellipse cx="72" cy="24" rx="9" ry="6" transform="rotate(18 72 24)" fill="#FFD54F" stroke="#263238" stroke-width="2.5"/>
        <!-- Tiny Horns -->
        <path d="M26 16C24 9 29 7 32 13" fill="#FFFFFF" stroke="#263238" stroke-width="2.5" stroke-linecap="round"/>
        <path d="M62 16C64 9 59 7 56 13" fill="#FFFFFF" stroke="#263238" stroke-width="2.5" stroke-linecap="round"/>
        <!-- Head -->
        <path d="M20 46V27C20 15 30 9 44 9C58 9 68 15 68 27V46H20Z" fill="#FFFFFF" stroke="#263238" stroke-width="2.8" stroke-linejoin="round"/>
        <!-- Patch spot -->
        <path d="M51 11C59 13 64 18 65 25C58 26 52 21 51 11Z" fill="#45C486"/>
        <!-- Eyes -->
        <circle cx="34" cy="24" r="2.8" fill="#263238"/>
        <circle cx="54" cy="24" r="2.8" fill="#263238"/>
        <!-- Snout -->
        <rect x="29" y="29" width="30" height="14" rx="7" fill="#FFEAA0" stroke="#263238" stroke-width="2.4"/>
        <circle cx="37" cy="36" r="1.8" fill="#263238"/>
        <circle cx="51" cy="36" r="1.8" fill="#263238"/>
        <!-- Tiny Paws gripping top edge -->
        <ellipse cx="15" cy="46" rx="7" ry="4.5" fill="#FFFFFF" stroke="#263238" stroke-width="2.4"/>
        <ellipse cx="73" cy="46" rx="7" ry="4.5" fill="#FFFFFF" stroke="#263238" stroke-width="2.4"/>
    </svg>
@endif

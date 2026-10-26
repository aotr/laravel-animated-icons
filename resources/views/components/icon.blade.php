@if (is_array($icon))
    <span data-animated-icon="{{ $name }}"
        @if ($trigger !== 'hover') data-animated-icon-on="{{ $trigger }}" @endif
        @if ($speed !== null) data-animated-icon-speed="{{ $speed }}" @endif
        class="inline-flex shrink-0 items-center justify-center">
        <svg {{ $attributes->merge(['class' => '']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            aria-hidden="true">
            {!! $icon['markup'] !!}
        </svg>
    </span>
@endif

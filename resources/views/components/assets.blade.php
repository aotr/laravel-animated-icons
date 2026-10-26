@php($nonce = $attributes->get('nonce'))

<script type="application/json" id="animated-icons-manifest" @if ($nonce) nonce="{{ $nonce }}" @endif>@json($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
<script src="{{ $asset }}" defer @if ($nonce) nonce="{{ $nonce }}" @endif></script>

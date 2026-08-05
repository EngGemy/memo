@props(['tone' => 'wait'])
<span {{ $attributes->merge(['class' => "tag {$tone} notch"]) }}>{{ $slot }}</span>

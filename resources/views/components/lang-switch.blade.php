@props(['class' => 'lang'])
@php
  $qs = request()->except('lang');
@endphp
<div {{ $attributes->merge(['class' => $class]) }}>
  <a href="{{ url()->current().'?'.http_build_query($qs + ['lang' => 'ar']) }}"
     class="{{ $locale === 'ar' ? 'on' : '' }}">ع</a>
  <a href="{{ url()->current().'?'.http_build_query($qs + ['lang' => 'en']) }}"
     class="{{ $locale === 'en' ? 'on' : '' }}">EN</a>
</div>

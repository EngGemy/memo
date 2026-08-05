@props(['tag' => 'div'])
<{{ $tag }} {{ $attributes->merge(['class' => 'nc']) }}>
  {{ $slot }}
</{{ $tag }}>

@props(['label' => '', 'value' => '', 'hint' => '', 'tone' => ''])
<div {{ $attributes->merge(['class' => 'kpi notch']) }}>
  <span>{{ $label }}</span>
  <b>{{ $value }}</b>
  @if($hint !== '' || $tone !== '')
    <i class="{{ $tone }}">{{ $hint }}</i>
  @endif
</div>

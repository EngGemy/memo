@props(['video'])
@php
  $title = $locale === 'ar' && ($video['title_ar'] ?? null) ? $video['title_ar'] : $video['title'];
  $desc  = $locale === 'ar' && ($video['description_ar'] ?? null) ? $video['description_ar'] : ($video['description'] ?? '');
  $cat   = $video['category'] ?? null;
  $catName = $cat ? ($locale === 'ar' && ($cat['name_ar'] ?? null) ? $cat['name_ar'] : $cat['name']) : '';
  $mm = sprintf('%02d:%02d', intdiv((int)($video['duration'] ?? 0), 60), ((int)($video['duration'] ?? 0)) % 60);
@endphp
<article class="card nc" data-slug="{{ $video['slug'] }}">
  <div class="th">
    @if(!empty($video['poster']))
      <img src="{{ $video['poster'] }}" alt="" loading="lazy">
    @endif
    <span class="pl"><i></i></span>
    <span class="d">{{ $mm }}</span>
  </div>
  <div class="bd">
    @if($catName)<span class="ct">{{ $catName }}</span>@endif
    <h3>{{ $title }}</h3>
    @if($desc)<p>{{ $desc }}</p>@endif
    <div class="ft">
      <span>{{ $video['views'] ?? 0 }} {{ __('memo.library.views_unit') }}</span>
      <a class="vc" href="{{ url('/verify/'.$video['verify_code']) }}">{{ $video['verify_code'] }}</a>
    </div>
  </div>
</article>

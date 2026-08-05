@extends('layouts.public')

@section('title', __('memo.brand'))

@section('content')
@php($brand = \App\Models\Setting::brand())
<div class="glow"></div>
<header>
  <nav class="w">
    <a href="{{ url('/') }}"><img src="{{ asset($brand['logo_path']) }}" alt="{{ __('memo.brand') }}"></a>
    <div class="sp">
      <x-lang-switch />
      <a class="btn g nc" href="#lib">{{ __('memo.nav.videos') }}</a>
      <a class="btn nc" href="https://wa.me/201095236175">{{ __('memo.nav.contact') }}</a>
    </div>
  </nav>
</header>

<section class="hero w">
  <img src="{{ asset($brand['logo_path']) }}" alt="{{ __('memo.brand') }}">
  <h1>{{ __('memo.hero.title') }}<br><em>{{ __('memo.hero.title_em') }}</em></h1>
  <p>{{ __('memo.hero.subtitle') }}</p>
  <div class="cta">
    <a class="btn nc" href="#lib">{{ __('memo.hero.cta_browse') }}</a>
    <a class="btn g nc" href="#prot">{{ __('memo.hero.cta_protect') }}</a>
  </div>
</section>

<section id="lib" class="w">
  <div class="hd">
    <div class="eb">{{ __('memo.library.eyebrow') }}</div>
    <h2>{{ __('memo.library.title') }}</h2>
    <p>{{ __('memo.library.subtitle') }}</p>
  </div>
  <div class="tabs" id="tabs"></div>
  <div class="grid" id="grid"></div>
  <div class="empty" id="empty" style="display:none">{{ __('memo.library.empty') }}</div>
</section>

<section id="prot">
  <div class="w">
    <div class="hd">
      <div class="eb">{{ __('memo.protect.eyebrow') }}</div>
      <h2>{{ __('memo.protect.title') }}</h2>
      <p>{{ __('memo.protect.subtitle') }}</p>
    </div>
    <div class="pcards">
      @foreach(__('memo.protect.cards') as $i => $card)
        @php($icons = ['◈','✓','⏱','⚑'])
        <div class="pc nc">
          <div class="ic nc">{{ $icons[$i] ?? '◈' }}</div>
          <h3>{{ $card['title'] }}</h3>
          <p>{{ $card['body'] }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

<footer>
  <div class="w">
    <img src="{{ asset($brand['logo_path']) }}" alt="{{ __('memo.brand') }}">
    <div class="ch">
      <a href="https://wa.me/201095236175">{{ __('memo.footer.channels.whatsapp1') }}</a>
      <a href="https://wa.me/201091349700">{{ __('memo.footer.channels.whatsapp2') }}</a>
      <a href="https://instagram.com/memo__store11">{{ __('memo.footer.channels.instagram') }}</a>
      <a href="https://tiktok.com/@memo__store11">{{ __('memo.footer.channels.tiktok') }}</a>
    </div>
    <small>{{ __('memo.footer.copyright') }}</small>
  </div>
</footer>

<div class="modal" id="modal">
  <div class="mbox nc">
    <button class="mx" id="mx" type="button">✕</button>
    <div style="position:relative">
      <video id="vid" playsinline controls controlsList="nodownload noplaybackrate" disablePictureInPicture></video>
      <span class="trace" id="trace"></span>
    </div>
    <div class="mi"><h3 id="mt"></h3><span class="vc" id="mv"></span></div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.13/dist/hls.min.js"></script>
<script>
window.MEMO_LANDING = {
  locale: @json($locale),
  strings: {
    all: @json(__('memo.library.tab_all')),
    empty: @json(__('memo.library.empty')),
    load_error: @json(__('memo.library.load_error')),
    views: @json(__('memo.library.views_unit')),
    play_error: @json(__('memo.player.play_error')),
  }
};
</script>
<script src="{{ asset('js/landing.js') }}" defer></script>
@endpush

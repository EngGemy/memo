@php($brand = \App\Models\Setting::brand())
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ __('memo.brand') }} — {{ __('memo.admin.nav.videos') }}</title>
<link rel="icon" href="{{ asset('assets/memo-mark.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;600;700&family=Inter+Tight:wght@400;500;600&family=JetBrains+Mono:wght@400;500&family=Cairo:wght@600;700;900&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/memo.css') }}">
@include('partials.brand')
</head>
<body>
@php($panel = $panel ?? 'videos')
<div class="shell">
  <aside id="side">
    <div class="alogo"><img id="logoSide" src="{{ asset($brand['logo_path']) }}" alt="{{ __('memo.brand') }}"></div>
    <nav id="nav">
      <h4>{{ __('memo.admin.nav.lib') }}</h4>
      <a href="{{ route('admin.dashboard') }}" class="{{ $panel === 'videos' ? 'on' : '' }}">{{ __('memo.admin.nav.videos') }}</a>
      <a href="{{ route('admin.categories') }}" class="{{ $panel === 'categories' ? 'on' : '' }}">{{ __('memo.admin.nav.cats') }}</a>
      <a href="{{ route('admin.upload') }}" class="{{ $panel === 'upload' ? 'on' : '' }}">{{ __('memo.admin.nav.upload') }}</a>
      <h4>{{ __('memo.admin.nav.brand') }}</h4>
      <a href="{{ route('admin.brand') }}" class="{{ $panel === 'brand' ? 'on' : '' }}">{{ __('memo.admin.nav.wm') }}</a>
      <h4>{{ __('memo.admin.nav.protect') }}</h4>
      <a href="{{ route('admin.leaks') }}" class="{{ $panel === 'leaks' ? 'on' : '' }}">{{ __('memo.admin.nav.leaks') }}</a>
      <a href="{{ route('admin.activity') }}" class="{{ $panel === 'activity' ? 'on' : '' }}">{{ __('memo.admin.nav.activity') }}</a>
    </nav>
  </aside>

  <main>
    <div class="top">
      <button id="mbtn" type="button">☰</button>
      <h1 id="viewTitle">@yield('title', __('memo.admin.nav.videos'))</h1>
      <span id="liveTag" class="tag run notch"><span class="spin"></span></span>
      <div class="sp">
        <x-lang-switch />
        <button class="btn ghost sm notch" id="refresh" type="button">{{ __('memo.admin.nav.refresh') }}</button>
        <form method="POST" action="{{ route('logout') }}" style="display:inline">@csrf
          <button class="btn ghost sm notch" type="submit">Logout</button>
        </form>
      </div>
    </div>

    <div class="kpis" id="kpis"></div>

    @yield('content')
  </main>
</div>

<div class="toast notch" id="toast"></div>

{{-- Edit video modal (videos page) --}}
<div class="modal" id="editModal">
  <div class="mbox notch">
    <header>
      <h2>{{ __('memo.admin.edit.title') }}</h2>
      <button class="mx" id="editClose" type="button">✕</button>
    </header>
    <div class="in">
      <input type="hidden" id="eId">
      <div class="ef">
        <div><label class="f">{{ __('memo.admin.upload.t_en') }}</label><input type="text" id="eTitle" class="notch"></div>
        <div><label class="f">{{ __('memo.admin.upload.t_ar') }}</label><input type="text" id="eTitleAr" class="notch" dir="rtl"></div>
        <div><label class="f">{{ __('memo.admin.edit.desc_en') }}</label><textarea id="eDesc" class="notch" rows="2"></textarea></div>
        <div><label class="f">{{ __('memo.admin.edit.desc_ar') }}</label><textarea id="eDescAr" class="notch" rows="2" dir="rtl"></textarea></div>
        <div><label class="f">{{ __('memo.admin.upload.cat') }}</label><select id="eCat" class="notch"></select></div>
        <div><label class="f">{{ __('memo.admin.edit.verify_link') }}</label>
          <a id="eVerify" class="mono" href="#" target="_blank" rel="noopener" style="display:block;padding-top:10px"></a></div>
      </div>
      <div class="poster-box">
        <img id="ePoster" src="" alt="">
        <div>
          <label class="f">{{ __('memo.admin.edit.poster') }}</label>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button class="btn ghost sm notch" id="ePosterBtn" type="button">{{ __('memo.admin.edit.upload_poster') }}</button>
            <button class="btn ghost sm notch" id="ePosterReset" type="button">{{ __('memo.admin.edit.reset_poster') }}</button>
            <input type="file" id="ePosterFile" accept="image/jpeg,image/png,image/webp" hidden>
          </div>
        </div>
      </div>
      <div style="display:flex;gap:9px;margin-top:8px">
        <button class="btn notch" id="eSave" type="button">{{ __('memo.admin.edit.save') }}</button>
        <button class="btn ghost notch" id="eCancel" type="button">{{ __('memo.admin.edit.cancel') }}</button>
      </div>
    </div>
  </div>
</div>

<script>
window.MEMO = {
  locale: @json($locale),
  panel: @json($panel),
  logo: @json(asset($brand['logo_path'])),
  T: @json(__('memo.js')),
  routes: {
    overview: @json(route('admin.overview')),
    categories: @json(route('admin.categories.index')),
    categoriesReorder: @json(route('admin.categories.reorder')),
    videosReorder: @json(route('admin.videos.reorder')),
    brand: @json(route('admin.brand.show')),
    brandSave: @json(route('admin.brand.update')),
    uploads: @json(route('admin.uploads.open')),
    leaks: @json(route('admin.leaks.store')),
  }
};
</script>
<script src="{{ asset('js/admin.js') }}" defer></script>
@stack('scripts')
</body>
</html>

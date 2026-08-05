@extends('layouts.admin')
@section('title', __('memo.admin.nav.upload'))
@section('content')
<section data-panel="upload">
  <div class="panel notch">
    <header>
      <h2>{{ __('memo.admin.upload.h') }}</h2>
      <small>{{ __('memo.admin.upload.sub') }}</small>
    </header>
    <div class="in">
      <div class="drop notch" id="drop">
        <b>{{ __('memo.admin.upload.drop') }}</b>
        <small>{{ __('memo.admin.upload.hint') }}</small>
        <div style="margin-top:12px"><button class="btn sm notch" id="browse" type="button">{{ __('memo.admin.upload.browse') }}</button></div>
        <input type="file" id="file" accept="video/*" hidden>
      </div>
      <div class="fields">
        <div><label class="f">{{ __('memo.admin.upload.t_en') }}</label><input type="text" id="tEn" class="notch"></div>
        <div><label class="f">{{ __('memo.admin.upload.t_ar') }}</label><input type="text" id="tAr" class="notch" dir="rtl"></div>
        <div><label class="f">{{ __('memo.admin.upload.cat') }}</label><select id="cat" class="notch"></select></div>
        <div><label class="f">{{ __('memo.admin.upload.exp') }}</label><select id="exp" class="notch"><option value="">—</option></select></div>
      </div>
      <div style="margin-top:11px">
        <label class="f">{{ __('memo.admin.upload.desc') }}</label>
        <textarea id="desc" class="notch" rows="2"></textarea>
      </div>
      <div style="margin-top:14px;display:flex;gap:9px;align-items:center;flex-wrap:wrap">
        <button class="btn notch" id="go" disabled type="button">{{ __('memo.admin.upload.start') }}</button>
        <button class="btn ghost notch sm hide" id="abort" type="button">{{ __('memo.admin.upload.cancel') }}</button>
        <span class="mono" id="hint"></span>
      </div>
      <div class="queue" id="queue"></div>
    </div>
  </div>
</section>
@endsection

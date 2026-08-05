@extends('layouts.admin')
@section('title', __('memo.admin.nav.wm'))
@section('content')
<section data-panel="brand">
  <div class="panel notch">
    <header>
      <h2>{{ __('memo.admin.brand.h') }}</h2>
      <small>{{ __('memo.admin.brand.sub') }}</small>
    </header>
    <div class="in wmgrid">
      <div class="stagebox notch"><canvas id="wmCanvas" width="640" height="360"></canvas></div>
      <div>
        <label class="f">{{ __('memo.admin.brand.phone') }}</label>
        <input type="text" id="wmPhone" class="notch" dir="ltr" value="01095236175">

        <div class="slider" style="margin-top:14px">
          <div class="row"><label>{{ __('memo.admin.brand.size') }}</label><output id="oSize">18%</output></div>
          <input type="range" id="rSize" min="8" max="34" value="18">
        </div>
        <div class="slider">
          <div class="row"><label>{{ __('memo.admin.brand.op') }}</label><output id="oOp">72%</output></div>
          <input type="range" id="rOp" min="25" max="100" value="72">
        </div>
        <div>
          <label class="f">{{ __('memo.admin.brand.pos') }}</label>
          <select id="wmPos" class="notch">
            <option value="br">{{ __('memo.admin.brand.br') }}</option>
            <option value="bl">{{ __('memo.admin.brand.bl') }}</option>
            <option value="tr">{{ __('memo.admin.brand.tr') }}</option>
            <option value="tl">{{ __('memo.admin.brand.tl') }}</option>
          </select>
        </div>

        <div class="slider" style="margin-top:16px">
          <div class="row"><label>{{ __('memo.admin.brand.nav') }}</label><output id="oNav">32 px</output></div>
          <input type="range" id="rNav" min="18" max="64" value="32">
        </div>
        <div class="slider">
          <div class="row"><label>{{ __('memo.admin.brand.hero') }}</label><output id="oHero">76 px</output></div>
          <input type="range" id="rHero" min="40" max="180" value="76">
        </div>
        <div class="slider">
          <div class="row"><label>{{ __('memo.admin.brand.foot') }}</label><output id="oFoot">40 px</output></div>
          <input type="range" id="rFoot" min="18" max="90" value="40">
        </div>

        <div style="display:flex;gap:9px;flex-wrap:wrap;margin-top:6px">
          <button class="btn notch" id="bSave" type="button">{{ __('memo.admin.brand.save') }}</button>
          <button class="btn ghost sm notch" id="bLogo" type="button">{{ __('memo.admin.brand.newlogo') }}</button>
          <input type="file" id="logoFile" accept="image/png,image/webp,image/svg+xml" hidden>
        </div>
        <p style="font-size:12px;color:var(--steel);margin-top:10px">{{ __('memo.admin.brand.note') }}</p>
      </div>
    </div>
  </div>
</section>
@endsection

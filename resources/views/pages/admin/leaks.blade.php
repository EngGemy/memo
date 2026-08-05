@extends('layouts.admin')
@section('title', __('memo.admin.nav.leaks'))
@section('content')
<section data-panel="leaks">
  <div class="panel notch">
    <header>
      <h2>{{ __('memo.admin.leaks.h') }}</h2>
      <small>{{ __('memo.admin.leaks.sub') }}</small>
    </header>
    <div class="in">
      <div class="fields" style="grid-template-columns:1fr 180px 200px 190px">
        <div><label class="f">{{ __('memo.admin.leaks.url') }}</label><input type="url" id="lkUrl" class="notch" dir="ltr" placeholder="https://..."></div>
        <div><label class="f">{{ __('memo.admin.leaks.plat') }}</label><input type="text" id="lkPlat" class="notch" placeholder="TikTok"></div>
        <div><label class="f">{{ __('memo.admin.leaks.who') }}</label><input type="text" id="lkWho" class="notch" dir="ltr"></div>
        <div><label class="f">{{ __('memo.admin.leaks.vid') }}</label><select id="lkVid" class="notch"></select></div>
      </div>
      <div style="margin-top:13px"><button class="btn notch" id="lkAdd" type="button">{{ __('memo.admin.leaks.add') }}</button></div>
    </div>
  </div>
  <div class="panel notch">
    <header>
      <h2>{{ __('memo.admin.leaks.list') }}</h2>
      <small id="lkCount"></small>
    </header>
    <div class="scroll">
      <table id="lkTable">
        <thead>
          <tr>
            <th>{{ __('memo.admin.leaks.url2') }}</th>
            <th>{{ __('memo.admin.leaks.plat') }}</th>
            <th>{{ __('memo.admin.leaks.orig') }}</th>
            <th>{{ __('memo.admin.videos.status') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
    <div class="empty hide" id="lkEmpty">{{ __('memo.admin.leaks.empty') }}</div>
  </div>
</section>
@endsection

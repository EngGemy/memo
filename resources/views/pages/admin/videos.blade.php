@extends('layouts.admin')
@section('title', __('memo.admin.nav.videos'))
@section('content')
<section data-panel="videos">
  <div class="panel notch">
    <header>
      <h2>{{ __('memo.admin.videos.h') }}</h2>
      <small id="vCount"></small>
    </header>
    <div class="scroll">
      <table id="vTable">
        <thead>
          <tr>
            <th style="width:28px"></th>
            <th>{{ __('memo.admin.videos.title') }}</th>
            <th>{{ __('memo.admin.videos.cat') }}</th>
            <th>{{ __('memo.admin.videos.dur') }}</th>
            <th>{{ __('memo.admin.videos.views') }}</th>
            <th>{{ __('memo.admin.videos.wm') }}</th>
            <th>{{ __('memo.admin.videos.code') }}</th>
            <th>{{ __('memo.admin.videos.status') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
    <div class="empty hide" id="vEmpty">{{ __('memo.admin.videos.empty') }}</div>
  </div>
</section>
@endsection

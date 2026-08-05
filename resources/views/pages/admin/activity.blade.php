@extends('layouts.admin')
@section('title', __('memo.admin.nav.activity'))
@section('content')
<section data-panel="activity">
  <div class="panel notch">
    <header><h2>{{ __('memo.admin.activity.h') }}</h2></header>
    <div class="in" id="topList"></div>
  </div>
  <div class="panel notch">
    <header>
      <h2>{{ __('memo.admin.activity.log') }}</h2>
      <small>{{ __('memo.admin.activity.live') }}</small>
    </header>
    <div class="in" id="actList" style="padding-top:6px"></div>
  </div>
</section>
@endsection

@extends('layouts.public')

@section('title', __('memo.verify.page_title'))
@section('body_attr') class="verify-page" @endsection

@section('content')
@php($brand = \App\Models\Setting::brand())
@php($ok = !empty($payload['verified']))
<div class="glow"></div>
<div class="card">
  <img class="logo" src="{{ asset($brand['logo_path']) }}" alt="{{ __('memo.brand') }}">
  <div id="out">
    @if($ok)
      <span class="badge y">{{ __('memo.verify.verified_badge') }}</span>
      <h1>{{ ($locale === 'ar' && ($payload['title_ar'] ?? null)) ? $payload['title_ar'] : $payload['title'] }}</h1>
      <p>{{ __('memo.verify.verified_body', ['owner' => $payload['owner']]) }}</p>
      <div class="rows">
        <div class="row"><span>{{ __('memo.verify.row_code') }}</span><b class="mono">{{ $payload['verify_code'] }}</b></div>
        <div class="row"><span>{{ __('memo.verify.row_first_published') }}</span><b class="mono">{{ $payload['first_published_at'] ?? '—' }}</b></div>
        <div class="row"><span>{{ __('memo.verify.row_duration') }}</span>
          <b class="mono">{{ sprintf('%d:%02d', intdiv((int)$payload['duration'], 60), ((int)$payload['duration']) % 60) }}</b>
        </div>
      </div>
      <div class="ch">
        <a href="{{ $payload['watch_url'] }}">{{ __('memo.verify.watch_original') }}</a>
        <a href="https://wa.me/20{{ ltrim($payload['channels']['whatsapp'], '0') }}">
          {{ __('memo.verify.whatsapp', ['number' => $payload['channels']['whatsapp']]) }}
        </a>
        <a href="https://instagram.com/{{ $payload['channels']['instagram'] }}">{{ '@'.$payload['channels']['instagram'] }}</a>
      </div>
      <p class="note">{{ __('memo.verify.verified_note') }}</p>
    @else
      <span class="badge n">{{ __('memo.verify.unverified_badge') }}</span>
      <h1>{{ __('memo.verify.unverified_title') }}</h1>
      <p>{{ __('memo.verify.unverified_body', ['code' => $code]) }}</p>
      <div class="ch">
        <a href="https://wa.me/201095236175">{{ __('memo.footer.channels.whatsapp1') }}</a>
        <a href="https://instagram.com/memo__store11">{{ __('memo.footer.channels.instagram') }}</a>
        <a href="{{ url('/') }}">{{ __('memo.verify.all_videos_link') }}</a>
      </div>
      <p class="note">{{ __('memo.verify.unverified_note') }}</p>
    @endif
  </div>
</div>
@endsection

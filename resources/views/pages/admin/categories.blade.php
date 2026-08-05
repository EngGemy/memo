@extends('layouts.admin')
@section('title', __('memo.admin.categories.title'))
@section('content')
<section data-panel="categories">
  <div class="panel notch">
    <header>
      <h2>{{ __('memo.admin.categories.h') }}</h2>
      <small id="cCount"></small>
    </header>
    <div class="in">
      <div class="fields" style="grid-template-columns:1fr 1fr 140px">
        <div><label class="f">{{ __('memo.admin.categories.name_en') }}</label><input type="text" id="cName" class="notch"></div>
        <div><label class="f">{{ __('memo.admin.categories.name_ar') }}</label><input type="text" id="cNameAr" class="notch" dir="rtl"></div>
        <div style="display:flex;align-items:flex-end"><button class="btn notch" id="cAdd" type="button">{{ __('memo.admin.categories.add') }}</button></div>
      </div>
    </div>
    <div id="cList"></div>
    <div class="empty hide" id="cEmpty">{{ __('memo.admin.categories.empty') }}</div>
  </div>
</section>
@endsection

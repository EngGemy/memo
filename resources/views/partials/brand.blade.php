{{-- Brand variables, written once per request from the settings table. --}}
@php($brand = \App\Models\Setting::brand())
<style>
  :root{
    --logo-nav:{{ $brand['logo_nav'] }}px;
    --logo-hero:{{ $brand['logo_hero'] }}px;
    --logo-foot:{{ $brand['logo_foot'] }}px;
  }
</style>
<script>window.MEMO_LOGO = @json(asset($brand['logo_path']));</script>

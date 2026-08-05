<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>دخول — MEMO STORE</title>
<link rel="icon" href="/assets/memo-mark.png">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@700;900&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
<style>
:root{--ink:#04060F;--shard:#0A1024;--volt:#1B4DFF;--beam:#5B8CFF;--chrome:#F2F5FF;
      --steel:#7C89A8;--line:rgba(91,140,255,.16);--bad:#FF4D6D;--nc:16px}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--ink);color:var(--chrome);font-family:'Tajawal',sans-serif;
     min-height:100vh;display:grid;place-items:center;padding:22px}
.glow{position:fixed;width:80vw;height:80vw;border-radius:50%;top:-42vw;left:50%;
      transform:translateX(-50%);filter:blur(40px);pointer-events:none;
      background:radial-gradient(circle,rgba(27,77,255,.24),transparent 62%)}
.box{--nc:20px;position:relative;width:min(420px,100%);background:var(--shard);
     border:1px solid var(--line);padding:36px 30px;
     clip-path:polygon(0 0,calc(100% - var(--nc)) 0,100% var(--nc),100% 100%,var(--nc) 100%,0 calc(100% - var(--nc)));
     box-shadow:0 40px 90px -34px rgba(27,77,255,.5)}
img.logo{height:48px;width:auto;margin:0 auto 24px;display:block;
         filter:drop-shadow(0 0 26px rgba(27,77,255,.65))}
h1{font-family:'Cairo',sans-serif;font-weight:900;font-size:20px;text-align:center;margin-bottom:6px}
p.sub{color:var(--steel);font-size:13.5px;text-align:center;margin-bottom:26px}
label{display:block;font-size:12.5px;color:var(--steel);margin-bottom:6px}
input[type=email],input[type=password]{width:100%;background:#060B1C;border:1px solid var(--line);
  color:var(--chrome);padding:12px 14px;font-family:inherit;font-size:14px;margin-bottom:16px;--nc:8px;
  clip-path:polygon(0 0,calc(100% - var(--nc)) 0,100% var(--nc),100% 100%,var(--nc) 100%,0 calc(100% - var(--nc)))}
input:focus{outline:none;border-color:var(--volt)}
.row{display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--steel)}
button{--nc:10px;width:100%;background:var(--volt);color:#fff;border:0;padding:13px;cursor:pointer;
  font-family:'Cairo',sans-serif;font-weight:700;font-size:14.5px;transition:.2s;
  clip-path:polygon(0 0,calc(100% - var(--nc)) 0,100% var(--nc),100% 100%,var(--nc) 100%,0 calc(100% - var(--nc)))}
button:hover{transform:translateY(-2px);box-shadow:0 12px 30px -12px var(--volt)}
.err{--nc:8px;background:rgba(255,77,109,.1);border:1px solid rgba(255,77,109,.4);color:var(--bad);
  padding:11px 14px;font-size:13px;margin-bottom:18px;
  clip-path:polygon(0 0,calc(100% - var(--nc)) 0,100% var(--nc),100% 100%,var(--nc) 100%,0 calc(100% - var(--nc)))}
a.back{display:block;text-align:center;margin-top:20px;font-size:12.5px;color:var(--steel);text-decoration:none}
a.back:hover{color:var(--chrome)}
</style>
</head>
<body>
<div class="glow"></div>
<div class="box">
  <img class="logo" src="/assets/memo-logo.png" alt="MEMO STORE">
  <h1>لوحة التحكم</h1>
  <p class="sub">الدخول مخصّص لإدارة المحتوى فقط</p>

  @if ($errors->any())
    <div class="err">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('login.attempt') }}">
    @csrf
    <label for="email">البريد الإلكتروني</label>
    <input id="email" type="email" name="email" dir="ltr" value="{{ old('email') }}" required autofocus>

    <label for="password">كلمة المرور</label>
    <input id="password" type="password" name="password" dir="ltr" required>

    <div class="row">
      <input type="checkbox" name="remember" id="remember" value="1">
      <label for="remember" style="margin:0">تذكّرني</label>
    </div>

    <button type="submit">دخول</button>
  </form>

  <a class="back" href="/">العودة إلى الموقع</a>
</div>
</body>
</html>
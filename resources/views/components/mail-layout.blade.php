<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#F5F0E8;font-family:Arial,sans-serif">
  <div style="max-width:560px;margin:40px auto;background:#0D0D0D;border-radius:16px;overflow:hidden">
    <div style="padding:24px 32px;border-bottom:1px solid #1A1A1A;text-align:center">
      <span style="font-size:20px;font-weight:700;color:#F5F0E8;font-family:Georgia,serif">Trimly<span style="color:#C9A84C">.</span></span>
    </div>
    <div style="padding:32px">
      <h2 style="color:#F5F0E8;font-size:20px;margin:0 0 16px;font-family:Georgia,serif">{{ $title }}</h2>
      <div style="color:#A0A0A0;font-size:14px;line-height:1.7">
        {!! $slot ?? '' !!}
      </div>
      @isset($ctaText)
        <div style="text-align:center;margin:28px 0">
          <a href="{{ $ctaUrl }}" style="background:#C9A84C;color:#0D0D0D;font-weight:700;font-size:14px;padding:14px 32px;border-radius:10px;text-decoration:none;display:inline-block">{{ $ctaText }}</a>
        </div>
      @endisset
      <p style="color:#484848;font-size:11px;margin-top:24px">Si no esperabas este email, podés ignorarlo. El enlace expira en 1 hora.</p>
    </div>
    <div style="padding:16px 32px;border-top:1px solid #1A1A1A;text-align:center">
      <p style="color:#484848;font-size:11px;margin:0">&copy; {{ date('Y') }} Trimly &middot; <a href="{{ config('app.url') }}" style="color:#C9A84C">trimly.com.ar</a></p>
    </div>
  </div>
</body>
</html>

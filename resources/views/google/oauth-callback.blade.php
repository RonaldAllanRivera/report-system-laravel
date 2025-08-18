<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Google Connected</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; display:flex; align-items:center; justify-content:center; height:100vh; margin:0; color:#334155; background:#f8fafc; }
        .card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:24px 28px; box-shadow: 0 10px 25px rgba(0,0,0,.05); text-align:center; }
        h1 { margin:0 0 8px; font-size:18px; }
        p { margin:0; font-size:14px; color:#64748b; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Google account connected</h1>
        <p>You can close this tab. Returning to the app…</p>
    </div>
    <script>
        (function(){
            try {
                if (window.opener) {
                    window.opener.postMessage({ type: 'google-auth-success' }, '*');
                }
            } catch (e) {}
            setTimeout(function(){ try { window.close(); } catch(e) {} }, 1200);
        })();
    </script>
</body>
</html>

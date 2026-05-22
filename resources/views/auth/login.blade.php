<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transportes Castillo - Acceso</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-art" aria-label="Presentacion">
            <canvas id="login-canvas" aria-hidden="true"></canvas>
            <div class="login-art-frame" aria-hidden="true">
                <div class="geo geo-line"></div>
                <div class="geo geo-block"></div>
                <div class="geo geo-panel"></div>
                <div class="geo geo-square"></div>
                <div class="geo geo-outline"></div>
            </div>
        </section>

        <section class="login-panel" aria-labelledby="login-title">
            <form class="login-form" method="POST" action="{{ route('login.submit') }}">
                @csrf
                <span class="login-kicker">Acceso seguro</span>
                <h2 id="login-title">Iniciar sesion</h2>
                <p>Ingresa tus credenciales para continuar.</p>

                <label>
                    <span>Usuario</span>
                    <input type="text" name="usuario" value="{{ old('usuario') }}" autocomplete="username" autofocus>
                </label>

                <label>
                    <span>Contrasena</span>
                    <input type="password" name="password" autocomplete="current-password">
                </label>

                @if ($errors->any())
                    <div class="form-alert">{{ $errors->first() }}</div>
                @endif

                <button class="button button-primary login-submit" type="submit">Ingresar</button>
                <small>Acceso autorizado solo para personal registrado.</small>
            </form>
        </section>
    </main>
</body>
</html>

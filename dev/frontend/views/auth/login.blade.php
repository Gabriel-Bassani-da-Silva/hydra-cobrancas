@extends('layouts.app')

@section('title', 'Login')

@section('body_class', 'login-page')

@section('hide_header', true)

@section('content')
<h1 id="titulo">Login</h1>

@if (session('success_message'))
    <p class="flash-success">{{ session('success_message') }}</p>
@endif

@if ($errors->any())
    <ul class="flashes flash-error">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<form action="{{ route('login') }}" method="POST">
    @csrf
    <div class="input-group">
        <label for="username">Usuário</label>
        <input type="text" id="username" name="username" placeholder="usuário" value="{{ old('username') }}" required autofocus />
    </div>

    <div class="input-group" style="position: relative;">
        <label for="password">Senha</label>
        <input type="password" id="password" name="password" placeholder="••••••••" required style="padding-right: 40px;" />
        <button type="button" id="togglePassword" style="position: absolute; right: 15px; bottom: 12px; background: none; border: none; cursor: pointer; padding: 0; outline: none; opacity: 0.3; display: flex; align-items: center; justify-content: center;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
        </button>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function (e) {
            const passwordInput = document.getElementById('password');
            const svgIcon = this.querySelector('svg');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                // Ícone de olho cortado (eye-off)
                svgIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                passwordInput.type = 'password';
                // Ícone de olho normal (eye)
                svgIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        });
    </script>

    <button type="submit" class="login-btn">Entrar</button>
</form>
@endsection

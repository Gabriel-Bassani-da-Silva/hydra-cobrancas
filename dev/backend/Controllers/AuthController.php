<?php

namespace App\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Proteção básica contra força bruta: 5 tentativas por usuário/IP por minuto.
        $throttleKey = strtolower($credentials['username']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'username' => "Muitas tentativas de login. Tente novamente em {$seconds} segundos.",
            ]);
        }

        $user = \App\Models\Colaborador::where('NOME_COLABORADOR', $credentials['username'])->first();

        if ($user && $this->validarSenha($credentials['password'], $user)) {
            RateLimiter::clear($throttleKey);

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended('/');
        }

        RateLimiter::hit($throttleKey);

        return back()->withErrors([
            'username' => 'Usuário ou senha inválidos.',
        ])->onlyInput('username');
    }

    /**
     * Valida a senha do colaborador.
     *
     * Para garantir a transição segura de senhas legadas (que podem ter sido
     * armazenadas em texto plano), validamos o hash bcrypt e, como fallback,
     * a comparação direta. Sempre que uma senha legada em texto plano for
     * validada com sucesso, ela é imediatamente re-hasheada (rehash) e
     * persistida, eliminando o texto plano do banco de forma transparente.
     */
    private function validarSenha(string $senhaInformada, \App\Models\Colaborador $user): bool
    {
        $senhaArmazenada = (string) $user->SENHA;

        // Caminho normal: senha já está em formato de hash.
        $pareceHash = Hash::isHashed($senhaArmazenada);

        if ($pareceHash && Hash::check($senhaInformada, $senhaArmazenada)) {
            // Atualiza o algoritmo/custo do hash caso necessário.
            if (Hash::needsRehash($senhaArmazenada)) {
                $this->atualizarSenhaHash($user, $senhaInformada);
            }
            return true;
        }

        // Fallback legado: senha em texto plano no banco.
        // Se bater, migramos imediatamente para bcrypt.
        if (!$pareceHash && hash_equals($senhaArmazenada, $senhaInformada)) {
            $this->atualizarSenhaHash($user, $senhaInformada);
            return true;
        }

        return false;
    }

    /**
     * Persiste a senha do colaborador como hash bcrypt.
     */
    private function atualizarSenhaHash(\App\Models\Colaborador $user, string $senhaPlana): void
    {
        $user->SENHA = Hash::make($senhaPlana);
        $user->save();
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}

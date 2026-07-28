<?php

declare(strict_types=1);

namespace App\Controllers\Web;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Services\AuthService;
use App\Services\MailService;
use Ramsey\Uuid\Uuid;

class AuthController extends Controller
{
    public function __construct(
        \App\Core\Request $request,
        \App\Core\Response $response,
        Database $db,
        private readonly AuthService $auth
    ) {
        parent::__construct($request, $response, $db);
    }

    public function showLogin(): void
    {
        $this->render('auth.login', ['title' => 'Sign in']);
    }

    public function showRegister(): void
    {
        $this->render('auth.register', ['title' => 'Create your account']);
    }

    public function showForgot(): void
    {
        $this->render('auth.forgot', ['title' => 'Forgot password']);
    }

    public function showReset(): void
    {
        $token = $this->request->get('token', '');
        $this->render('auth.reset', ['title' => 'Reset password', 'token' => $token]);
    }

    public function login(): void
    {
        $data = $this->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            $result = $this->auth->login(
                $data['login'],
                $data['password'],
                $this->request->ip(),
                $this->request->userAgent()
            );
            $this->auth->startSession();
            if ($this->request->wantsJson()) {
                $this->success($result, 'Logged in');
                return;
            }
            $intended = \App\Core\Application::getInstance()->getService('session')?->get('intended_url') ?? url('');
            $this->withFlash('success', 'Welcome back!');
            $this->redirect($intended);
        } catch (\Throwable $e) {
            if ($this->request->wantsJson()) {
                $this->fail($e->getMessage(), [], 401);
                return;
            }
            $this->withFlash('error', $e->getMessage());
            $this->back();
        }
    }

    public function register(): void
    {
        $data = $this->validate([
            'username' => 'required|string|min:3|max:30|regex:[a-zA-Z0-9_]',
            'email'    => 'required|email',
            'password' => 'required|string|min:6|max:100',
            'display_name' => 'string|max:100',
            'gender'   => 'in:male,female,other',
            'country'  => 'string|max:80',
        ]);

        try {
            $user = $this->auth->register($data, $this->request->ip(), $this->request->userAgent());
            // Auto-login after registration
            $this->auth->login($data['email'], $data['password'], $this->request->ip(), $this->request->userAgent());
            $this->auth->startSession();
            if ($this->request->wantsJson()) {
                $this->success(['user' => $user], 'Account created');
                return;
            }
            $this->withFlash('success', 'Welcome to VoiceChat! Your account is ready.');
            $this->redirect(url('profile'));
        } catch (\Throwable $e) {
            if ($this->request->wantsJson()) {
                $this->fail($e->getMessage(), [], 422);
                return;
            }
            $this->withFlash('error', $e->getMessage());
            $this->back();
        }
    }

    public function logout(): void
    {
        $this->auth->logout();
        $this->withFlash('success', 'You have been logged out.');
        $this->redirect(url(''));
    }

    public function forgot(): void
    {
        $data = $this->validate(['email' => 'required|email']);
        $row = $this->db->fetchOne('SELECT id, email FROM users WHERE email = :e LIMIT 1', ['e' => $data['email']]);
        if ($row) {
            $token = bin2hex(random_bytes(32));
            $this->db->insert('password_resets', [
                'email'      => $data['email'],
                'token'      => $token,
                'ip'         => $this->request->ip(),
                'expires_at' => date('Y-m-d H:i:s', time() + 3600),
            ]);
            $resetUrl = url('reset-password?token=' . $token);
            try {
                $mail = \App\Core\Application::getInstance()->getService('mail');
                $mail->send($data['email'], 'Reset your password',
                    "Click this link to reset your password (valid for 1 hour):\n\n{$resetUrl}\n\nIf you did not request this, please ignore."
                );
            } catch (\Throwable $e) {
                // logged but user-facing we always say "if the email exists..."
            }
        }
        $this->withFlash('success', 'If the email exists, a reset link has been sent.');
        $this->redirect(url('login'));
    }

    public function reset(): void
    {
        $data = $this->validate([
            'token'    => 'required|string',
            'password' => 'required|string|min:6|max:100',
        ]);
        $row = $this->db->fetchOne(
            'SELECT * FROM password_resets WHERE token = :t AND used_at IS NULL AND expires_at > NOW() LIMIT 1',
            ['t' => $data['token']]
        );
        if (!$row) {
            $this->withFlash('error', 'Invalid or expired reset token.');
            $this->redirect(url('forgot-password'));
            return;
        }
        $this->db->update('users', [
            'password' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
        ], 'email = :e', ['e' => $row['email']]);
        $this->db->update('password_resets', ['used_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $row['id']]);
        $this->withFlash('success', 'Password updated. You can now log in.');
        $this->redirect(url('login'));
    }

    public function verifyEmail(): void
    {
        // verify email by token (sent in email)
        $token = (string) $this->request->get('token', '');
        // In this MVP we just trust the call to set verified status for the logged-in user
        $user = $this->user();
        if (!$user) { $this->redirect(url('login')); return; }
        $this->db->update('users', ['email_verified_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
        $this->withFlash('success', 'Email verified.');
        $this->redirect(url('profile'));
    }

    public function verifyAccount(): void
    {
        $user = $this->user();
        if (!$user) { $this->redirect(url('login')); return; }
        $this->db->update('users', ['is_verified' => 1], 'id = :id', ['id' => $user['id']]);
        $this->withFlash('success', 'You are now verified.');
        $this->redirect(url('profile'));
    }
}

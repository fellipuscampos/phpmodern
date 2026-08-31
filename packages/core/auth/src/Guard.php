<?php

declare(strict_types=1);

namespace PhpModern\Auth;

/**
 * A second (or third...) independently-tracked "who is logged in", for an
 * app that needs more than one kind of principal live at once — an admin
 * panel guard alongside the regular user guard, say. `Auth`'s own
 * login()/logout()/id()/check()/requireLogin() are untouched by this: they
 * remain the single "web" guard every existing caller already uses, backed
 * by the exact same session key as before. `Guard` is purely additive,
 * reached through `Auth::guard($name)`, for anything beyond that one
 * principal — never a replacement for `Auth`'s existing static API.
 */
final class Guard
{
    private readonly string $sessionKey;

    public function __construct(public readonly string $name)
    {
        $this->sessionKey = "phpmodern_auth_{$name}_user_id";
    }

    public function login(int $userId): void
    {
        Session::start();
        Session::set($this->sessionKey, $userId);
        Session::regenerateId(); // a new session id after every privilege change, to prevent session fixation
    }

    public function logout(): void
    {
        Session::start();
        Session::remove($this->sessionKey);
        Session::regenerateId();
    }

    public function id(): ?int
    {
        Session::start();
        $id = Session::get($this->sessionKey);

        return is_int($id) ? $id : null;
    }

    public function check(): bool
    {
        return $this->id() !== null;
    }

    /** Ends the request with 401 unless someone is logged in through this guard. */
    public function requireLogin(): void
    {
        if (!$this->check()) {
            http_response_code(401);
            echo 'Login required.';
            exit;
        }
    }
}

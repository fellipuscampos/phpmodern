<?php

declare(strict_types=1);

namespace PhpModern\Auth;

/**
 * Tracks who is logged in — nothing more. Fetching the actual user record
 * (name, email, roles...) from the database by id() is the caller's job,
 * the same way this framework keeps push and persistence as separate
 * listeners in Store rather than one class doing everything.
 */
final class Auth
{
    private const SESSION_KEY = 'phpmodern_auth_user_id';

    public static function login(int $userId): void
    {
        Session::start();
        Session::set(self::SESSION_KEY, $userId);
        Session::regenerateId(); // a new session id after every privilege change, to prevent session fixation
    }

    public static function logout(): void
    {
        Session::start();
        Session::remove(self::SESSION_KEY);
        Session::regenerateId();
    }

    public static function id(): ?int
    {
        Session::start();
        $id = Session::get(self::SESSION_KEY);

        return is_int($id) ? $id : null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    /** Ends the request with 401 unless someone is logged in. */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            http_response_code(401);
            echo 'Login required.';
            exit;
        }
    }

    /**
     * A second (or third...) independently-tracked login, for an app that
     * needs more than one kind of principal at once — see Guard's own
     * docblock. Every call with the same $name returns behaviorally
     * equivalent access to that guard's session slot; Guard holds no state
     * of its own besides the name, so a fresh instance per call is fine.
     */
    public static function guard(string $name): Guard
    {
        return new Guard($name);
    }
}

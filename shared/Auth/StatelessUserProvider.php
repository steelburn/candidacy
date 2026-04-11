<?php

namespace Shared\Auth;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class StatelessUserProvider implements UserProvider
{
    public function retrieveById($identifier)
    {
        // This is called by JWTAuth to hydrate the user
        // We return a user with just the ID, it will be fully hydrated
        // from claims in other methods if needed, but usually JWTAuth
        // doesn't call this for stateless.
        return new StatelessUser(['user_id' => $identifier]);
    }

    public function retrieveByToken($identifier, $token)
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        //
    }

    public function retrieveByCredentials(array $credentials)
    {
        return null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return false;
    }
}

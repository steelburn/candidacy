<?php

namespace Shared\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

class StatelessUser implements Authenticatable
{
    public $id;
    public $tenant_id;
    public $roles;
    protected $claims;

    public function __construct(array $claims)
    {
        $this->claims = $claims;
        $this->id = $claims['user_id'] ?? $claims['sub'] ?? null;
        $this->tenant_id = $claims['tenant_id'] ?? null;
        $this->roles = $claims['roles'] ?? [];
    }

    public function __get($key)
    {
        return $this->claims[$key] ?? null;
    }

    public function __isset($key)
    {
        return isset($this->claims[$key]);
    }

    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return $this->id;
    }

    public function getAuthPassword()
    {
        return '';
    }

    public function getRememberToken()
    {
        return '';
    }

    public function setRememberToken($value)
    {
        // Not used
    }

    public function getRememberTokenName()
    {
        return '';
    }

    // Custom accessors for easy use in microservices
    public function getTenantId()
    {
        return $this->tenant_id;
    }

    public function getRoles()
    {
        return $this->roles;
    }
}

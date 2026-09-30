<?php

namespace App\Enums;

enum UserRole: string
{
    case Client = 'client';
    case Agent = 'agent';
    case Admin = 'admin';

    public function label(): string
    {
        return __("enums.user_role.{$this->value}");
    }
}

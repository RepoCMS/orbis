<?php

declare(strict_types=1);

namespace App\Identity\Domain\Enums;

enum AuthEvent: string
{
    case LOGIN_SUCCESS                  = 'LOGIN_SUCCESS';
    case LOGIN_FAILED                   = 'LOGIN_FAILED';
    case MFA_FAILED                     = 'MFA_FAILED';
    case MFA_SETUP_COMPLETED            = 'MFA_SETUP_COMPLETED';
    case MFA_BACKUP_CODE_USED           = 'MFA_BACKUP_CODE_USED';
    case MFA_BACKUP_CODES_REGENERATED   = 'MFA_BACKUP_CODES_REGENERATED';
    case ACCOUNT_LOCKED                 = 'ACCOUNT_LOCKED';
    case ACCOUNT_UNLOCK_REQUESTED       = 'ACCOUNT_UNLOCK_REQUESTED';
    case ACCOUNT_UNLOCKED               = 'ACCOUNT_UNLOCKED';
    case ACTIVATION_COMPLETED           = 'ACTIVATION_COMPLETED';
    case PASSWORD_RESET_REQUESTED       = 'PASSWORD_RESET_REQUESTED';
    case PASSWORD_RESET_COMPLETED       = 'PASSWORD_RESET_COMPLETED';
    case LOGOUT                         = 'LOGOUT';
}

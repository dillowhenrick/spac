<?php

namespace App\Enums;

enum VerificationDecision: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
}

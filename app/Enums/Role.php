<?php

namespace App\Enums;

enum Role: string
{
    case Requester = 'requester';
    case AmlakasVerifier = 'amlakas_verifier';
    case InstitutionManager = 'institution_manager';
    case InstitutionStaff = 'institution_staff';
}

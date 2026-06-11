<?php

namespace App\Enums;

enum AuditEvent: string
{
    // Authentication
    case Login = 'login';
    case Logout = 'logout';
    case LoginFailed = 'login_failed';
    case TwoFactorSuccess = 'two_factor_success';
    case TwoFactorFailed = 'two_factor_failed';

    // Request lifecycle
    case RequestCreated = 'request_created';
    case RequestSubmitted = 'request_submitted';
    case RequestVerified = 'request_verified';
    case RequestRejected = 'request_rejected';
    case RequestRouted = 'request_routed';

    // Assignment
    case AssignedToSelf = 'assigned_to_self';
    case AssignedToStaff = 'assigned_to_staff';
    case AssignmentReplaced = 'assignment_replaced';

    // Response
    case ResponseDrafted = 'response_drafted';
    case ResponseSubmitted = 'response_submitted';
    case ResponseApproved = 'response_approved';
    case ResponseRejected = 'response_rejected';
    case ResponseReleased = 'response_released';

    // Messaging
    case MessageSent = 'message_sent';

    // Files
    case FileUploaded = 'file_uploaded';

    // Closure
    case RequestClosed = 'request_closed';
}

<?php

namespace App\Enums;

enum RequestStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderVerification = 'under_verification';
    case VerificationRejected = 'verification_rejected';
    case Verified = 'verified';
    case Routed = 'routed';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case PendingManagerReview = 'pending_manager_review';
    case RevisionRequested = 'revision_requested';
    case Approved = 'approved';
    case Released = 'released';
    case Closed = 'closed';
}

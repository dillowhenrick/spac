# AMLakas Secure Request Portal

## Overview

The AMLakas Secure Request Portal is a secure request management and communication platform that enables law enforcement agencies, government institutions, and authorized organizations to submit requests, exchange documents, communicate securely, and manage approvals through a centralized workflow.

The system is inspired by platforms such as Kodex and is designed to provide a secure and auditable channel between requesters (e.g., NBI, PNP, AMLC) and participating institutions (e.g., MLhuillier, LBC, Cebuana).

---

# Objectives

* Eliminate reliance on email-only request processing
* Provide strong requester authentication and verification
* Centralize request management
* Enable secure document exchange
* Support manager/staff assignment workflows
* Maintain complete audit trails
* Provide real-time notifications
* Enable secure messaging between parties

---

# User Roles

## Requester

Examples:

* NBI
* PNP
* AMLC
* Courts
* Government Agencies

Capabilities:

* Submit requests
* Upload multiple attachments
* Track request status
* Receive notifications
* Exchange messages
* Download responses

---

## AMLakas Verifier

Capabilities:

* Verify requesters
* Verify documents
* Authenticate senders
* Route requests
* Monitor audit logs
* Reject unauthorized requests

---

## Institution Manager

Examples:

* MLhuillier Manager
* LBC Manager

Capabilities:

* Review incoming requests
* Assign requests
* Assign to self
* Assign to staff
* Assign to multiple staff
* Review responses
* Approve responses
* Release responses
* Message requester
* View audit trails

---

## Institution Staff

Capabilities:

* View assigned requests
* Review documents
* Upload response files
* Submit responses
* Exchange messages
* Add internal notes

---

# Security Features

## Authentication

* Email login
* Password authentication
* Two-factor authentication (2FA)

Supported methods:

* Email OTP
* Authenticator application

---

## Security Logging

Capture:

* IP Address
* Browser Information
* Device Information
* Login Timestamp
* Session History

---

## Verification Controls

* Agency verification
* Official email verification
* Sender authorization validation
* Document authenticity review
* Manual AMLakas verification

---

# Request Lifecycle

## 1. User Login

Requester logs into AMLakas.

Security checks:

* Username/Email
* Password
* Two-factor authentication
* IP logging
* Device logging
* Browser logging

---

## 2. Request Submission

Requester submits a new request.

Fields:

* Request Type
* Reference Number
* Subject
* Description
* Target Institution

Attachments:

* Multiple file uploads

Examples:

* Court Order
* Warrant
* Investigation Request
* Supporting Documents

---

## 3. AMLakas Verification Queue

AMLakas receives the request.

Verification includes:

* Requester verification
* Agency verification
* Authorization verification
* Document review

Possible outcomes:

### Verified

Request proceeds to routing.

### Rejected

Requester receives notification.

---

## 4. Request Routing

AMLakas routes the verified request to the appropriate institution.

Examples:

* MLhuillier
* LBC
* Cebuana
* Other AMLakas clients

---

## 5. Institution Manager Review

Manager receives notification.

Manager reviews:

* Request details
* Requester details
* Attachments
* Verification status
* Audit history

---

## 6. Assignment

Manager can:

### Assign to Self

Manager handles request personally.

### Assign to Single Staff

Manager assigns one employee.

### Assign to Multiple Staff

Manager selects multiple users using checkboxes.

Example:

* Staff A
* Staff B
* Staff C

---

## 7. Staff Processing

Assigned staff members process the request.

Capabilities:

* Review request
* Review attachments
* Upload response files
* Add notes
* Exchange messages

Attachments support:

* Multiple file uploads

---

## 8. Submit to Manager

Staff submits completed response to manager.

Status:

* Pending Approval

---

## 9. Manager Review and Approval

Manager reviews submitted response.

Actions:

### Approve

Response is accepted.

### Reject

Returned to staff.

### Return for Revision

Staff updates response and resubmits.

---

## 10. Release Response

Manager performs final release.

Only released responses are visible to requester.

Status:

* Released

---

## 11. Requester Notification

Requester receives:

### In-App Notification

Example:

"Your request has been completed."

### Email Notification

Response available for download.

---

## 12. Response Download

Requester downloads:

* Response files
* Supporting documents
* Generated reports

---

## 13. Request Closure

Request is marked completed.

Status:

* Closed

---

# Messaging System

Secure communication is available throughout the lifecycle.

Participants:

* Requester
* Manager
* Assigned Staff

Capabilities:

* Send messages
* Receive messages
* Attach files
* Maintain conversation history

Examples:

* Clarification requests
* Additional document requests
* Follow-up questions

---

# Notifications

## Requester Notifications

* Request submitted
* Request verified
* Request rejected
* Response released
* New message received

---

## Institution Notifications

* New request received
* Request assigned
* New message received
* Response pending approval

---

## Manager Notifications

* New request received
* Staff response submitted
* Approval required

---

# Audit Trail

Every action is logged.

## Audit Events

### Authentication

* Login
* Logout
* Failed login
* 2FA success
* 2FA failure

### Request Events

* Request created
* Request submitted
* Request verified
* Request rejected
* Request routed

### Assignment Events

* Assigned to self
* Assigned to staff
* Assignment removed
* Multiple assignment created

### File Events

* File uploaded
* File downloaded
* File deleted

### Messaging Events

* Message sent
* Message received

### Approval Events

* Response submitted
* Response approved
* Response rejected
* Response released

### Closure Events

* Request closed

---

## Audit Information Captured

For every event:

* User
* Role
* Timestamp
* IP Address
* Browser
* Device
* Action Performed
* Previous Value
* New Value

---

# Queue Filters

## AMLakas Verification Queue

Filters:

* Status
* Agency
* Institution
* Date Range
* Priority

---

## Institution Queue

Filters:

* Status
* Assigned User
* Agency
* Date Range
* Reference Number

---

## Requester Dashboard

Filters:

* Open
* Pending
* Closed
* Rejected
* Date Range

---

# Request Statuses

```text
Submitted
Pending AMLakas Verification
Verified
Rejected
Routed
Assigned
In Progress
Pending Manager Review
Approved
Released
Closed
```

---

# High-Level Workflow

```text
Requester
    ↓
Login + 2FA
    ↓
Submit Request
    ↓
AMLakas Verification
    ↓
Route to Institution
    ↓
Manager Review
    ↓
Assign Self / Staff / Multiple Staff
    ↓
Staff Processing
    ↓
Submit Response
    ↓
Manager Approval
    ↓
Manager Release
    ↓
Requester Notification
    ↓
Requester Downloads Response
    ↓
Request Closed
```

---

# Future Enhancements

## Phase 2

* SLA Tracking
* Escalation Rules
* Analytics Dashboard
* Reporting Dashboard
* Digital Signatures
* Request Templates
* Agency Whitelisting
* Case Management Dashboard
* Mobile Application
* Advanced Search
* API Integrations

---

# Current Delivery Baseline

The current repository already includes:

* Laravel 13 backend
* Inertia v3 with React 19 frontend
* Fortify authentication flows
* Email verification
* Two-factor authentication
* Passkeys support
* Settings and security pages
* Pest feature test setup
* Wayfinder route generation

This means the first delivery phase should focus on the secure request workflow itself, not on rebuilding authentication from scratch.

---

# MVP Scope

The initial MVP should deliver the full request lifecycle for verified users and participating institutions.

Included in MVP:

* Requester account access
* AMLakas verifier review queue
* Institution routing
* Manager assignment workflows
* Staff response preparation
* Manager approval and release
* Secure file uploads and downloads
* In-app messaging
* Email and in-app notifications
* Full audit trail logging

Deferred from MVP:

* Analytics dashboards
* SLA automation
* External API integrations
* Mobile application
* Digital signatures
* Advanced search

---

# Core Modules

## 1. Identity and Access

Purpose:

* Authenticate users securely
* Enforce role-based access
* Protect institution-specific data

Roles:

* Requester
* AMLakas Verifier
* Institution Manager
* Institution Staff

---

## 2. Request Intake

Purpose:

* Allow verified requesters to create and submit requests

Capabilities:

* Create draft request
* Submit request
* Upload multiple attachments
* Tag target institution
* Track current status

---

## 3. Verification Queue

Purpose:

* Allow AMLakas staff to validate incoming requests before routing

Capabilities:

* Review requester details
* Review attachments
* Verify authorization
* Reject invalid requests
* Route verified requests

---

## 4. Institution Work Queue

Purpose:

* Give managers and staff a controlled workspace for handling routed requests

Capabilities:

* View institution-specific requests
* Filter by status, requester, date, and assignee
* Review verification notes
* Track deadlines and progress

---

## 5. Assignment and Collaboration

Purpose:

* Support manager-led work distribution

Capabilities:

* Assign to self
* Assign to one staff member
* Assign to multiple staff members
* Add internal notes
* Track assignment history

---

## 6. Response Approval and Release

Purpose:

* Ensure institution responses are reviewed before release

Capabilities:

* Staff draft response
* Upload response documents
* Submit for approval
* Manager approve, reject, or return for revision
* Release approved response to requester

---

## 7. Messaging and Document Exchange

Purpose:

* Keep all request-related communication inside the portal

Capabilities:

* Request-thread messaging
* File attachments in messages
* Conversation history
* Role-aware visibility rules

---

## 8. Audit and Notifications

Purpose:

* Make every critical action traceable and visible

Capabilities:

* Event logging
* Email notifications
* In-app notifications
* Security event tracking
* Download and access logging

---

# Proposed Domain Model

The following entities should be planned early because they drive both UI and permissions:

* `users` - existing application users
* `institutions` - participating institutions such as MLhuillier or LBC
* `agencies` - requesting organizations such as NBI or AMLC
* `memberships` - links users to agencies or institutions with role metadata
* `requests` - core request record
* `request_attachments` - uploaded supporting documents
* `request_verifications` - AMLakas review outcomes and notes
* `request_routes` - routing history from AMLakas to institutions
* `request_assignments` - manager-to-staff assignments
* `request_responses` - institution response drafts and submissions
* `response_attachments` - files attached to responses
* `request_messages` - secure request conversation thread
* `message_attachments` - files attached to messages
* `audit_logs` - immutable activity history

---

# Permission Model

Access should be enforced with Laravel policies and scoped queries.

## Requester

* Can create and view own requests
* Can upload request files
* Can view released responses
* Can send messages on own requests

## AMLakas Verifier

* Can review all submitted requests
* Can approve, reject, and route requests
* Can view verification and audit data

## Institution Manager

* Can view requests routed to their institution
* Can assign requests
* Can review staff output
* Can approve and release responses

## Institution Staff

* Can only access assigned requests
* Can draft responses
* Can upload response files
* Can send messages and internal notes

---

# Recommended Status Model

For implementation, the workflow should use stable machine-readable statuses with clear transitions.

Suggested statuses:

```text
draft
submitted
under_verification
verification_rejected
verified
routed
assigned
in_progress
pending_manager_review
revision_requested
approved
released
closed
```

Notes:

* `draft` is requester-only and not yet visible to AMLakas
* `verification_rejected` is a terminal or rework state depending on policy
* `revision_requested` returns work to assigned institution users
* `released` means requester can access final response
* `closed` means the request is fully completed and archived operationally

---

# Delivery Roadmap

## Phase 1 - Foundation and Access Control

Deliverables:

* Finalize role model
* Add institution and agency structures
* Add membership relationships
* Implement scoped authorization rules
* Reuse existing Fortify authentication and security features

Success criteria:

* Users can only access data for their role and organization

---

## Phase 2 - Request Submission

Deliverables:

* Request creation form
* Multi-file upload support
* Request listing for requesters
* Request detail page
* Draft and submit behavior

Success criteria:

* A requester can create and submit a complete request with attachments

---

## Phase 3 - AMLakas Verification and Routing

Deliverables:

* Verification queue
* Verification detail screen
* Approve and reject actions
* Routing to target institution
* Verification notes and audit events

Success criteria:

* AMLakas can review and route submitted requests end-to-end

---

## Phase 4 - Institution Processing

Deliverables:

* Institution request queue
* Assignment actions
* Staff work area
* Internal notes
* Response drafting and attachment uploads

Success criteria:

* Managers and staff can collaboratively process routed requests

---

## Phase 5 - Approval, Release, and Messaging

Deliverables:

* Manager approval flow
* Revision cycle support
* Response release action
* Request-thread messaging
* Notification events

Success criteria:

* An approved response can be released and communicated to the requester

---

## Phase 6 - Audit Hardening and Reporting

Deliverables:

* Complete audit log coverage
* Security event visibility
* Exportable logs or summary reports
* Queue performance improvements
* Operational dashboards for internal users

Success criteria:

* Sensitive actions are fully traceable and operational oversight is possible

---

# Technical Approach

Implementation should align with the current application stack.

Backend:

* Use Laravel policies for authorization
* Use Form Requests for validation
* Use queued notifications for email delivery
* Use private storage for uploaded files
* Use audit tables for activity history

Frontend:

* Use Inertia pages for role-specific dashboards
* Use shared layouts for requester, verifier, and institution workspaces
* Use Wayfinder-generated actions and routes instead of hardcoded URLs
* Reuse existing UI primitives already present in the project

Testing:

* Add Pest feature tests per workflow stage
* Test policies and scoped access
* Test upload authorization and secure downloads
* Test status transitions and release rules

---

# Initial Build Order

Recommended implementation order:

1. Roles, institutions, agencies, and memberships
2. Request model, attachments, and requester flows
3. AMLakas verification and routing
4. Institution queue and assignments
5. Response drafting and approval
6. Messaging and notifications
7. Audit completion and reporting

---

# Open Product Questions

These decisions should be clarified before development accelerates:

* Can one request be routed to multiple institutions at the same time?
* Can multiple assigned staff submit separate partial outputs?
* Can a rejected request be edited and resubmitted by the requester?
* Are internal notes visible only within the institution, or also to AMLakas?
* What file retention and deletion rules apply to sensitive documents?
* Is response release always manual, or can it be automated after approval?

---

# Phase 1 Execution Checklist

Phase 1 should establish access control and the first requester workflow slice. The goal is to create a stable domain foundation before AMLakas verification, institution routing, and approval flows are added.

## Phase 1 Goal

Deliver a secure requester-facing request submission module backed by role-aware organization data.

Success outcome:

* A requester can sign in, create a request, attach files, submit it, and view only their own requests
* Internal users can be modeled with organization membership and role metadata
* The application is ready for verification and routing in Phase 2

---

## Track 1 - Domain and Access Foundation

### 1.1 Create organization tables

Add:

* `agencies`
* `institutions`
* `memberships`

Suggested responsibilities:

* `agencies` represent request-origin organizations
* `institutions` represent target organizations receiving requests
* `memberships` connect users to an organization and role

Suggested membership fields:

* `user_id`
* `organization_type`
* `organization_id`
* `role`
* `is_primary`
* timestamps

Implementation notes:

* Use separate organization tables instead of storing everything on `users`
* Keep role assignment data relational and queryable
* Add indexes for `organization_type`, `organization_id`, and `role`

### 1.2 Extend application models

Create or update:

* `App\Models\Agency`
* `App\Models\Institution`
* `App\Models\Membership`
* `App\Models\User`

Add relationships for:

* user to memberships
* membership to user
* membership to agency or institution

Optional helper methods on `User`:

* `memberships()`
* `agencyMemberships()`
* `institutionMemberships()`
* `hasRole(string $role): bool`

### 1.3 Define role vocabulary

Establish an explicit role set early:

* `requester`
* `amlakas_verifier`
* `institution_manager`
* `institution_staff`

Implementation notes:

* Prefer a PHP enum or a dedicated constants class
* Use machine-readable role values consistently across backend and frontend

### 1.4 Share role context with Inertia

Extend shared props to expose the current user’s high-level access context.

Add to shared data:

* current memberships
* primary role
* current agency or institution summary

Use case:

* role-aware navigation
* conditional dashboard content
* route guards in frontend components

### 1.5 Add authorization layer

Create policies or equivalent access rules for:

* viewing requests
* creating requests
* uploading request attachments

Rules for Phase 1:

* requesters can only view their own requests
* internal roles should not gain broad access until verifier and institution flows are implemented

---

## Track 2 - Request Submission Foundation

### 2.1 Create request tables

Add:

* `requests`
* `request_attachments`

Suggested `requests` fields:

* `requester_id`
* `agency_id`
* `target_institution_id`
* `reference_number`
* `request_type`
* `subject`
* `description`
* `status`
* `submitted_at`
* timestamps

Suggested `request_attachments` fields:

* `request_id`
* `uploaded_by`
* `original_name`
* `path`
* `mime_type`
* `size`
* timestamps

Implementation notes:

* Start with `draft` and `submitted` statuses only for Phase 1
* Use private storage, not public asset-style storage
* Add indexes on `requester_id`, `agency_id`, `target_institution_id`, and `status`

### 2.2 Create request models

Create:

* `App\Models\Request`
* `App\Models\RequestAttachment`

Recommended relationships:

* request belongs to requester user
* request belongs to agency
* request belongs to target institution
* request has many attachments
* attachment belongs to request
* attachment belongs to uploader user

### 2.3 Define request status language

Use a stable machine-readable status model from the start.

Phase 1 statuses:

* `draft`
* `submitted`

Reserve later statuses for future phases without implementing them yet.

### 2.4 Create validation requests

Add Form Requests for:

* storing a draft request
* submitting a request
* uploading attachments

Validation should cover:

* required requester fields
* file MIME types
* file size limits
* target institution existence

---

## Track 3 - Requester Experience

### 3.1 Add requester routes

Create a dedicated route group for requester request flows.

Initial route set:

* list requests
* show create form
* store draft
* show request detail
* submit request

Route behavior:

* protect with `auth` and `verified`
* name routes consistently for Wayfinder generation

### 3.2 Add thin controllers

Create a dedicated controller for requester request flows.

Suggested actions:

* `index`
* `create`
* `store`
* `show`
* `submit`

Implementation notes:

* Keep controller methods thin
* Use Form Requests for validation
* Use policy authorization in each action

### 3.3 Add Inertia pages

Create initial pages for:

* requester request index
* requester request create form
* requester request detail

UI expectations:

* reuse existing app layout
* reuse current form primitives and headings
* use empty states when there are no requests
* display status clearly

### 3.4 Add role-aware navigation

Update app navigation to expose requester workflow links when the current user has requester access.

Initial nav entries:

* `Dashboard`
* `Requests`
* `New Request`

Implementation notes:

* use shared Inertia auth context
* keep links generated through Wayfinder or named route helpers

### 3.5 Add file upload support

Support multiple attachment uploads on request create and edit flows.

Requirements:

* private file storage
* validated file types
* secure download strategy for later phases
* attachment metadata persisted in database

Phase 1 note:

* download endpoints can be deferred if files remain visible only in requester detail views for now

---

## Track 4 - Test Coverage

Every Phase 1 change should be covered with focused Pest feature tests.

### 4.1 Membership and access tests

Add tests for:

* requester role can access requester routes
* non-requester roles are denied requester-only flows
* users cannot view requests they do not own

### 4.2 Request creation tests

Add tests for:

* create page renders
* draft request can be stored
* submitted request persists with `submitted` status
* validation errors are enforced

### 4.3 Attachment tests

Add tests for:

* valid files can be uploaded
* invalid file types are rejected
* oversized files are rejected

### 4.4 Inertia response tests

Add tests for:

* request index renders correct component
* request create page renders correct component
* request detail page renders only allowed data

---

## Suggested File Targets

Likely first files to add or update:

* new migrations for organizations, memberships, requests, and request attachments
* new models under `app/Models`
* new Form Requests under `app/Http/Requests`
* new controllers under `app/Http/Controllers`
* `routes/web.php` or a dedicated route file for requests
* `app/Http/Middleware/HandleInertiaRequests.php`
* new request pages under `resources/js/pages`
* navigation updates under `resources/js/components`
* new feature tests under `tests/Feature`

---

## Recommended Implementation Order

Build Phase 1 in this order:

1. Organizations, memberships, and role vocabulary
2. User relationships and shared Inertia role context
3. Request and attachment schema
4. Policies and authorization rules
5. Requester routes and controller actions
6. Requester Inertia pages and navigation
7. File upload support
8. Pest feature tests and cleanup

---

## Definition of Done for Phase 1

Phase 1 is complete when all of the following are true:

* requester organization membership exists and is queryable
* a requester can create a request with attachments
* a requester can submit and view the request
* another requester cannot access that request
* request pages are reachable through the app navigation
* relevant Pest feature tests pass

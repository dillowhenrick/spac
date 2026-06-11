<?php

use App\Enums\LegalProcess;
use App\Enums\RequestStatus;
use App\Models\Agency;
use App\Models\Institution;
use App\Models\Request;
use App\Models\RequestAttachment;
use App\Models\User;

test('request belongs to requester user', function () {
    $request = Request::factory()->create();

    expect($request->requester)->toBeInstanceOf(User::class);
});

test('request belongs to agency', function () {
    $request = Request::factory()->create();

    expect($request->agency)->toBeInstanceOf(Agency::class);
});

test('request belongs to target institution', function () {
    $request = Request::factory()->create();

    expect($request->targetInstitution)->toBeInstanceOf(Institution::class);
});

test('request has many attachments', function () {
    $request = Request::factory()->create();

    RequestAttachment::factory()->count(3)->create([
        'request_id' => $request->id,
        'uploaded_by' => $request->requester_id,
    ]);

    expect($request->attachments)->toHaveCount(3);
});

test('status is cast to RequestStatus enum', function () {
    $request = Request::factory()->draft()->create();

    expect($request->status)->toBe(RequestStatus::Draft);
});

test('legal_process is cast to LegalProcess enum', function () {
    $request = Request::factory()->create([
        'legal_process' => LegalProcess::Wdcd,
    ]);

    expect($request->legal_process)->toBe(LegalProcess::Wdcd);
});

test('isDraft returns true for draft status', function () {
    $request = Request::factory()->draft()->create();

    expect($request->isDraft())->toBeTrue();
});

test('isDraft returns false for submitted status', function () {
    $request = Request::factory()->submitted()->create();

    expect($request->isDraft())->toBeFalse();
});

test('submitted factory state sets submitted_at', function () {
    $request = Request::factory()->submitted()->create();

    expect($request->submitted_at)->not->toBeNull()
        ->and($request->status)->toBe(RequestStatus::Submitted);
});

test('withWarrant factory state sets warrant dates', function () {
    $request = Request::factory()->withWarrant()->create();

    expect($request->legal_process_signed_at)->not->toBeNull()
        ->and($request->warrant_expires_at)->not->toBeNull();
});

test('warrant_expires_at is 10 days after legal_process_signed_at', function () {
    $request = Request::factory()->withWarrant()->create();

    $diff = (int) $request->legal_process_signed_at->diffInDays($request->warrant_expires_at);

    expect($diff)->toBe(10);
});

test('attachment belongs to request', function () {
    $attachment = RequestAttachment::factory()->create();

    expect($attachment->request)->toBeInstanceOf(Request::class);
});

test('attachment belongs to uploader user', function () {
    $attachment = RequestAttachment::factory()->create();

    expect($attachment->uploader)->toBeInstanceOf(User::class);
});

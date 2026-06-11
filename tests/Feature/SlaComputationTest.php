<?php

use App\Enums\LegalProcess;
use App\Enums\RequestStatus;
use App\Models\Institution;
use App\Models\Request as AppRequest;
use App\Models\RequestAssignment;
use Illuminate\Support\Facades\Storage;

test('computeReturnDueAt returns executed_at plus 48h for warrant type with no expiry conflict', function () {
    $request = AppRequest::factory()->withWarrant()->make([
        'legal_process' => LegalProcess::Wdcd,
        'warrant_expires_at' => now()->addDays(8),
    ]);

    $executedAt = now()->toImmutable();
    $due = $request->computeReturnDueAt($executedAt);

    expect($due)->not->toBeNull()
        ->and($due->toDateTimeString())->toBe($executedAt->addHours(48)->toDateTimeString());
});

test('computeReturnDueAt uses warrant_expires_at when it is earlier than executed_at', function () {
    $expiresAt = now()->subHours(2)->toImmutable();

    $request = AppRequest::factory()->make([
        'legal_process' => LegalProcess::Wicd,
        'legal_process_signed_at' => now()->subDays(10),
        'warrant_expires_at' => $expiresAt,
    ]);

    $executedAt = now()->toImmutable();
    $due = $request->computeReturnDueAt($executedAt);

    expect($due->toDateTimeString())->toBe($expiresAt->addHours(48)->toDateTimeString());
});

test('computeReturnDueAt returns null for non-warrant types', function (LegalProcess $type) {
    $request = AppRequest::factory()->make(['legal_process' => $type]);
    $due = $request->computeReturnDueAt(now()->toImmutable());

    expect($due)->toBeNull();
})->with([
    LegalProcess::Subpoena,
    LegalProcess::CourtOrder,
    LegalProcess::EmergencyDisclosure,
]);

test('isWarrantType returns true for warrant types only', function (LegalProcess $type, bool $expected) {
    $request = AppRequest::factory()->make(['legal_process' => $type]);
    expect($request->isWarrantType())->toBe($expected);
})->with([
    [LegalProcess::Wdcd, true],
    [LegalProcess::Wicd, true],
    [LegalProcess::Wssecd, true],
    [LegalProcess::Wpecd, true],
    [LegalProcess::Subpoena, false],
    [LegalProcess::CourtOrder, false],
    [LegalProcess::EmergencyDisclosure, false],
]);

test('storeResponse sets executed_at and request_due_at when transitioning from assigned', function () {
    Storage::fake('local');

    $institution = Institution::factory()->create();
    $staff = staffUser($institution);
    $request = routedRequest($institution);

    $request->update([
        'status' => RequestStatus::Assigned,
        'legal_process' => LegalProcess::Wdcd->value,
        'legal_process_signed_at' => now()->subDays(2),
        'warrant_expires_at' => now()->addDays(8),
    ]);

    RequestAssignment::factory()->create([
        'request_id' => $request->id,
        'assigned_to' => $staff->id,
        'assigned_by' => $staff->id,
        'is_self_assigned' => true,
        'assigned_at' => now(),
    ]);

    $this->actingAs($staff)
        ->post(route('institution.response.store', $request), ['notes' => 'Draft'])
        ->assertRedirect();

    $request->refresh();

    expect($request->executed_at)->not->toBeNull()
        ->and($request->request_due_at)->not->toBeNull()
        ->and($request->executed_at->diffInHours($request->request_due_at))->toBe(48.0);
});

test('storeResponse does not set request_due_at for non-warrant type', function () {
    Storage::fake('local');

    $institution = Institution::factory()->create();
    $staff = staffUser($institution);
    $request = routedRequest($institution);

    $request->update([
        'status' => RequestStatus::Assigned,
        'legal_process' => LegalProcess::Subpoena->value,
    ]);

    RequestAssignment::factory()->create([
        'request_id' => $request->id,
        'assigned_to' => $staff->id,
        'assigned_by' => $staff->id,
        'is_self_assigned' => true,
        'assigned_at' => now(),
    ]);

    $this->actingAs($staff)
        ->post(route('institution.response.store', $request), ['notes' => 'Draft'])
        ->assertRedirect();

    expect($request->refresh()->request_due_at)->toBeNull();
});

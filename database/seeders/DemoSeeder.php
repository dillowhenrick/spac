<?php

namespace Database\Seeders;

use App\Enums\LegalProcess;
use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Models\Agency;
use App\Models\Institution;
use App\Models\Membership;
use App\Models\Request as AppRequest;
use App\Models\RequestAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── Organizations ─────────────────────────────────────────────────────

        $nbi = Agency::firstOrCreate(['code' => 'NBI'], [
            'name' => 'National Bureau of Investigation',
            'is_active' => true,
        ]);

        $pnp = Agency::firstOrCreate(['code' => 'PNP'], [
            'name' => 'Philippine National Police',
            'is_active' => true,
        ]);

        $mlhuillier = Institution::firstOrCreate(['code' => 'MLHUI'], [
            'name' => 'MLhuillier',
            'is_active' => true,
        ]);

        $lbc = Institution::firstOrCreate(['code' => 'LBC'], [
            'name' => 'LBC Express',
            'is_active' => true,
        ]);

        // ── Users ─────────────────────────────────────────────────────────────

        $requester = User::firstOrCreate(['email' => 'requester@demo.test'], [
            'name' => 'Juan dela Cruz',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $verifier = User::firstOrCreate(['email' => 'verifier@demo.test'], [
            'name' => 'Maria Santos',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $manager = User::firstOrCreate(['email' => 'manager@demo.test'], [
            'name' => 'Roberto Reyes',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $staff = User::firstOrCreate(['email' => 'staff@demo.test'], [
            'name' => 'Ana Gomez',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // ── Memberships ───────────────────────────────────────────────────────

        Membership::firstOrCreate(['user_id' => $requester->id, 'organization_type' => 'agency', 'organization_id' => $nbi->id], [
            'role' => Role::Requester,
            'is_primary' => true,
        ]);

        Membership::firstOrCreate(['user_id' => $verifier->id, 'organization_type' => 'agency', 'organization_id' => $nbi->id], [
            'role' => Role::AmlakasVerifier,
            'is_primary' => true,
        ]);

        Membership::firstOrCreate(['user_id' => $manager->id, 'organization_type' => 'institution', 'organization_id' => $mlhuillier->id], [
            'role' => Role::InstitutionManager,
            'is_primary' => true,
        ]);

        Membership::firstOrCreate(['user_id' => $staff->id, 'organization_type' => 'institution', 'organization_id' => $mlhuillier->id], [
            'role' => Role::InstitutionStaff,
            'is_primary' => true,
        ]);

        // ── Requests ──────────────────────────────────────────────────────────

        // Draft
        AppRequest::firstOrCreate(['reference_number' => 'SP-2026-0001'], [
            'requester_id' => $requester->id,
            'agency_id' => $nbi->id,
            'target_institution_id' => $mlhuillier->id,
            'legal_process' => LegalProcess::Subpoena,
            'status' => RequestStatus::Draft,
        ]);

        // Submitted
        $submitted = AppRequest::firstOrCreate(['reference_number' => 'SP-2026-0002'], [
            'requester_id' => $requester->id,
            'agency_id' => $nbi->id,
            'target_institution_id' => $mlhuillier->id,
            'legal_process' => LegalProcess::Subpoena,
            'status' => RequestStatus::Submitted,
            'submitted_at' => now()->subDays(1),
            'legal_process_signed_at' => now()->subDays(3),
        ]);

        // Routed (for manager queue)
        $routed = AppRequest::firstOrCreate(['reference_number' => 'SW-2026-0003'], [
            'requester_id' => $requester->id,
            'agency_id' => $pnp->id,
            'target_institution_id' => $mlhuillier->id,
            'legal_process' => LegalProcess::SearchWarrantDomesticUs,
            'status' => RequestStatus::Routed,
            'submitted_at' => now()->subDays(2),
            'legal_process_signed_at' => now()->subDays(5),
            'warrant_expires_at' => now()->subDays(5)->addDays(10),
            'request_due_at' => now()->subDays(5)->addDays(10)->addHours(48),
        ]);

        // Assigned (with staff assignment for work area)
        $assigned = AppRequest::firstOrCreate(['reference_number' => 'EM-2026-0004'], [
            'requester_id' => $requester->id,
            'agency_id' => $nbi->id,
            'target_institution_id' => $mlhuillier->id,
            'legal_process' => LegalProcess::Emergency,
            'status' => RequestStatus::Assigned,
            'submitted_at' => now()->subDays(3),
            'legal_process_signed_at' => now()->subDays(4),
        ]);

        RequestAssignment::firstOrCreate(
            ['request_id' => $assigned->id, 'assigned_to' => $staff->id],
            [
                'assigned_by' => $manager->id,
                'is_self_assigned' => false,
                'assigned_at' => now()->subDay(),
            ]
        );

        $this->command->info('Demo users created:');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Requester (NBI)', 'requester@demo.test', 'password'],
                ['AMLakas Verifier', 'verifier@demo.test', 'password'],
                ['Institution Manager (MLhuillier)', 'manager@demo.test', 'password'],
                ['Institution Staff (MLhuillier)', 'staff@demo.test', 'password'],
            ]
        );
    }
}

<?php

namespace Database\Seeders;

use App\Enums\ProgramRole;
use App\Models\Caregiver;
use App\Models\CaregiverAuthorization;
use App\Models\ClinicalProgram;
use App\Models\DiaryEntry;
use App\Models\GoalProgressReport;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Models\ProgramMember;
use App\Models\RecoveryGoal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PicsSyntheticDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $admin = User::query()->where('username', 'ADMIN')->firstOrFail();
            $program = ClinicalProgram::query()->where('code', 'PICS')->firstOrFail();

            ProgramMember::query()->updateOrCreate(
                ['clinical_program_id' => $program->id, 'user_id' => $admin->id],
                ['role' => ProgramRole::Leader, 'is_active' => true],
            );

            $patient = Patient::query()->updateOrCreate(
                ['identification' => 'SINTETICO-PICS-001'],
                [
                    'full_name' => 'María Esperanza Demo',
                    'sex' => 'F',
                    'age' => 62,
                    'email' => 'paciente.demo@koqoi.test',
                    'password' => 'DemoPICS2026!',
                    'must_change_password' => false,
                    'has_seen_portal_tour' => false,
                ],
            );

            $case = PicsCase::query()->updateOrCreate(
                ['patient_id' => $patient->id, 'clinical_program_id' => $program->id],
                [
                    'case_number' => 'PICS-DEMO-001',
                    'status' => 'in_review',
                    'clinical_stage' => 'seguimiento',
                    'enrollment_at' => now()->subDays(12),
                    'enrollment_source' => 'icu_discharge',
                    'mechanical_ventilation_days' => 6,
                    'delirium_days' => 2,
                    'icu_los_days' => 11,
                    'age_at_admission' => 62,
                    'barthel_at_discharge' => 70,
                    'shock_or_sepsis' => true,
                    'mrc_total' => 44,
                    'assigned_auditor_id' => $admin->id,
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ],
            );
            $case->recalculateRisk()->save();

            $caregiver = Caregiver::query()->updateOrCreate(
                ['email' => 'familiar.demo@koqoi.test'],
                [
                    'name' => 'Carlos Familiar Demo',
                    'phone' => '3000000000',
                    'password' => 'DemoPICS2026!',
                    'is_active' => true,
                    'must_change_password' => false,
                    'has_seen_portal_tour' => false,
                ],
            );

            CaregiverAuthorization::query()->updateOrCreate(
                ['pics_case_id' => $case->id, 'caregiver_id' => $caregiver->id],
                [
                    'relationship' => 'Hijo',
                    'can_write_diary' => true,
                    'can_access_journey' => true,
                    'authorized_by' => $admin->id,
                    'authorized_at' => now()->subDays(10),
                    'revoked_at' => null,
                ],
            );

            DiaryEntry::query()->updateOrCreate(
                ['pics_case_id' => $case->id, 'content' => 'Hoy caminó hasta la sala con apoyo y toleró bien el ejercicio.'],
                [
                    'authorable_type' => Caregiver::class,
                    'authorable_id' => $caregiver->id,
                    'entry_date' => today()->subDay(),
                    'visible_to_patient' => true,
                    'is_draft' => false,
                ],
            );

            $goal = RecoveryGoal::query()->updateOrCreate(
                ['pics_case_id' => $case->id, 'description' => 'Caminar 15 metros con acompañamiento'],
                [
                    'domain' => 'movilidad',
                    'measure' => 'Distancia caminada',
                    'unit' => 'metros',
                    'target_date' => today()->addDays(14),
                    'responsible_user_id' => $admin->id,
                    'status' => 'active',
                    'created_by' => $admin->id,
                ],
            );

            GoalProgressReport::query()->updateOrCreate(
                ['recovery_goal_id' => $goal->id, 'reporter_type' => Caregiver::class, 'reporter_id' => $caregiver->id],
                ['reported_at' => now()->subDay(), 'notes' => 'Logró 8 metros con caminador.', 'had_difficulty' => false],
            );
        });
    }
}

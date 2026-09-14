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

            $names = ['María Esperanza', 'Jorge Enrique', 'Lucía Fernanda', 'Pedro Antonio', 'Elena Patricia'];
            foreach ($names as $offset => $name) {
                $number = $offset + 1;
                $patient = Patient::query()->updateOrCreate(
                    ['identification' => sprintf('SINTETICO-PICS-%03d', $number)],
                    ['full_name' => $name.' Demo', 'sex' => $number % 2 ? 'F' : 'M', 'age' => 50 + ($number * 4),
                        'email' => "paciente{$number}@koqoi.test", 'portal_username' => "paciente{$number}",
                        'password' => '123456', 'must_change_password' => false, 'has_seen_portal_tour' => false],
                );
                $case = PicsCase::query()->updateOrCreate(
                    ['patient_id' => $patient->id, 'clinical_program_id' => $program->id],
                    ['case_number' => sprintf('PICS-DEMO-%03d', $number), 'case_sequence' => $number,
                        'status' => 'in_review', 'clinical_stage' => 'seguimiento',
                        'enrollment_at' => now()->subDays(7 + $number), 'enrollment_source' => 'icu_discharge',
                        'mechanical_ventilation_days' => 2 + $number, 'delirium_days' => $number % 3,
                        'icu_los_days' => 6 + $number, 'age_at_admission' => 50 + ($number * 4),
                        'barthel_at_discharge' => 60 + ($number * 5), 'shock_or_sepsis' => $number % 2 === 1,
                        'mrc_total' => 40 + $number, 'assigned_auditor_id' => $admin->id,
                        'created_by' => $admin->id, 'updated_by' => $admin->id],
                );
                $case->recalculateRisk()->save();
                $caregiver = Caregiver::query()->updateOrCreate(
                    ['email' => "familiar{$number}@koqoi.test"],
                    ['name' => "Familiar {$number} Demo", 'portal_username' => "familiar{$number}",
                        'phone' => '30000000'.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                        'password' => '123456', 'is_active' => true, 'must_change_password' => false,
                        'has_seen_portal_tour' => false],
                );
                CaregiverAuthorization::query()->updateOrCreate(
                    ['pics_case_id' => $case->id, 'caregiver_id' => $caregiver->id],
                    ['relationship' => 'Familiar', 'can_write_diary' => true, 'can_access_journey' => true,
                        'authorized_by' => $admin->id, 'authorized_at' => now()->subDays(6), 'revoked_at' => null],
                );
                DiaryEntry::query()->updateOrCreate(
                    ['pics_case_id' => $case->id, 'content' => "Registro sintético del caso {$number}: caminata y ejercicios tolerados."],
                    ['authorable_type' => Caregiver::class, 'authorable_id' => $caregiver->id,
                        'entry_date' => today()->subDay(), 'visible_to_patient' => true, 'is_draft' => false],
                );
                $goal = RecoveryGoal::query()->updateOrCreate(
                    ['pics_case_id' => $case->id, 'description' => 'Caminar '.(10 + $number * 5).' metros con acompañamiento'],
                    ['domain' => 'movilidad', 'measure' => 'Distancia caminada', 'unit' => 'metros',
                        'target_date' => today()->addDays(14), 'responsible_user_id' => $admin->id,
                        'status' => 'active', 'created_by' => $admin->id],
                );
                GoalProgressReport::query()->updateOrCreate(
                    ['recovery_goal_id' => $goal->id, 'reporter_type' => Caregiver::class, 'reporter_id' => $caregiver->id],
                    ['reported_at' => now()->subDay(), 'notes' => "Avance sintético del caso {$number}.", 'had_difficulty' => false],
                );
            }
        });
    }
}

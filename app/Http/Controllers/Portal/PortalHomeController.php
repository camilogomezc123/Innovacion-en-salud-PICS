<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Caregiver;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Support\Posuci\CaseAccess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PortalHomeController extends Controller
{
    /**
     * Puntos "de participación" por tipo de actividad — es una capa de motivación de
     * interfaz, no un puntaje clínico: nunca se guarda en base de datos, se calcula al
     * vuelo a partir de lo que el actor actual ya reportó.
     */
    private const POINTS = [
        'diary_entry' => 10,
        'goal_progress_report' => 15,
        'wellbeing_self_report' => 15,
        'passport_reported' => 25,
        'home_monitoring_reading' => 5,
        'education_viewed' => 10,
        'discharge_readiness_reviewed' => 10,
        'caregiver_journey_step' => 15,
        'support_request_created' => 5,
    ];

    private const POINTS_PER_LEVEL = 60;

    private const LEVEL_TITLES = [
        1 => 'Explorador/a',
        2 => 'Aventurero/a',
        3 => 'Guerrero/a de la recuperación',
        4 => 'Campeón/a',
        5 => 'Héroe/ína de POSUCI',
        6 => 'Leyenda de POSUCI',
    ];

    public function index(): View
    {
        $case = $this->currentCase();

        if (! $case) {
            return view('portal.home', ['case' => null]);
        }

        $patient = Auth::guard('patient')->user();
        $caregiver = Auth::guard('caregiver')->user();
        $actor = $patient instanceof Patient ? $patient : $caregiver;
        $canAccessJourney = $caregiver instanceof Caregiver && CaseAccess::caregiverCanAccessJourney($caregiver, $case);

        $case->loadMissing([
            'recoveryGoals.progressReports', 'educationAssignments.resource', 'dischargeReadinessCheck.items',
            'supportRequests', 'caregiverJourneySteps', 'diaryEntries', 'homeMonitoringReadings',
            'recoveryPassport', 'followups',
        ]);

        $pendingGoals = $case->recoveryGoals->where('status', 'active')->count();

        $unreadEducation = $case->educationAssignments
            ->filter(fn ($assignment) => $assignment->resource?->is_active && $assignment->viewed_at === null)
            ->count();

        $pendingReadinessItems = $case->dischargeReadinessCheck?->items
            ->filter(fn ($item) => $item->reviewed_at === null)
            ->count() ?? 0;

        $openSupportRequests = $case->supportRequests->whereNull('response_text')->count();

        $pendingJourneySteps = $canAccessJourney
            ? $case->caregiverJourneySteps->whereNull('reported_at')->count()
            : null;

        $gamification = $actor ? $this->computeGamification($case, $actor, $canAccessJourney) : null;

        $missions = collect([
            ['title' => 'Antes y ahora', 'description' => 'Tu pasaporte de recuperación.', 'url' => route('portal.passport'), 'icon' => '📖', 'color' => 'linear-gradient(135deg,#0ea5e9,#0e7490)'],
            ['title' => 'Mi diario', 'description' => 'Lo que tu familia — y tú — han escrito.', 'url' => route('portal.diary'), 'icon' => '✍️', 'color' => 'linear-gradient(135deg,#7c3aed,#5b21b6)'],
            ['title' => 'Mis metas', 'description' => 'Tus metas de recuperación y tu avance.', 'url' => route('portal.goals'), 'icon' => '🎯', 'color' => 'linear-gradient(135deg,#f97316,#ea580c)', 'pending' => $pendingGoals],
            ['title' => 'Cómo me siento', 'description' => 'Autorreporte de bienestar.', 'url' => route('portal.wellbeing'), 'icon' => '💙', 'color' => 'linear-gradient(135deg,#ec4899,#db2777)'],
            ['title' => 'Necesito ayuda', 'description' => 'Reporta una dificultad o una duda.', 'url' => route('portal.support'), 'icon' => '🆘', 'color' => 'linear-gradient(135deg,#ef4444,#b91c1c)', 'pending' => $openSupportRequests],
            ['title' => 'Medicamentos', 'description' => 'Los medicamentos conciliados por tu equipo.', 'url' => route('portal.medications'), 'icon' => '💊', 'color' => 'linear-gradient(135deg,#0ea5e9,#2563eb)'],
            ['title' => 'Monitoreo en casa', 'description' => 'Registra tus lecturas (saturación, presión, etc.).', 'url' => route('portal.home-monitoring'), 'icon' => '🩺', 'color' => 'linear-gradient(135deg,#22c55e,#15803d)'],
            ['title' => 'Preparación para el alta', 'description' => 'Temas clave antes de salir.', 'url' => route('portal.discharge-readiness'), 'icon' => '🎓', 'color' => 'linear-gradient(135deg,#facc15,#ca8a04)', 'pending' => $pendingReadinessItems],
            ['title' => 'Educación', 'description' => 'Contenido elegido para tu recuperación.', 'url' => route('portal.education'), 'icon' => '📚', 'color' => 'linear-gradient(135deg,#6366f1,#4338ca)', 'pending' => $unreadEducation],
        ]);

        if ($canAccessJourney) {
            $missions->push(['title' => 'Mi ruta como cuidador', 'description' => 'Pasos para prepararte a acompañar.', 'url' => route('portal.caregiver-journey'), 'icon' => '🤝', 'color' => 'linear-gradient(135deg,#14b8a6,#0f766e)', 'pending' => $pendingJourneySteps]);
        }

        return view('portal.home', [
            'case' => $case,
            'showTour' => $actor !== null && ! $actor->has_seen_portal_tour,
            'pendingGoals' => $pendingGoals,
            'unreadEducation' => $unreadEducation,
            'pendingReadinessItems' => $pendingReadinessItems,
            'openSupportRequests' => $openSupportRequests,
            'pendingJourneySteps' => $pendingJourneySteps,
            'missions' => $missions,
            'gamification' => $gamification,
            'actorFirstName' => $this->firstName($actor),
        ]);
    }

    /**
     * @return array{points: int, level: int, levelTitle: string, xpIntoLevel: int, xpForNextLevel: int, xpProgressPct: float, badges: array<int, array{icon: string, label: string, unlocked: bool}>, streakDays: int}
     */
    private function computeGamification(PicsCase $case, Patient|Caregiver $actor, bool $canAccessJourney): array
    {
        $actorType = $actor::class;
        $actorId = $actor->id;

        $diaryCount = $case->diaryEntries->where('authorable_type', $actorType)->where('authorable_id', $actorId)->count();
        $goalReportsCount = $case->recoveryGoals->flatMap->progressReports
            ->where('reporter_type', $actorType)->where('reporter_id', $actorId)->count();
        $wellbeingCount = $case->followups->where('submitted_by_type', $actorType)->where('submitted_by_id', $actorId)->count();
        $passportReportedByActor = $case->recoveryPassport
            && $case->recoveryPassport->reported_by_type === $actorType
            && $case->recoveryPassport->reported_by_id === $actorId;
        $monitoringCount = $case->homeMonitoringReadings->where('recorded_by_type', $actorType)->where('recorded_by_id', $actorId)->count();
        $educationViewedCount = $case->educationAssignments->where('viewed_by_type', $actorType)->where('viewed_by_id', $actorId)->count();
        $readinessReviewedCount = $case->dischargeReadinessCheck?->items
            ->where('reviewed_by_type', $actorType)->where('reviewed_by_id', $actorId)->count() ?? 0;
        $journeyStepsCount = $case->caregiverJourneySteps->where('reported_by_type', $actorType)->where('reported_by_id', $actorId)->count();
        $supportRequestsCount = $case->supportRequests->where('created_by_type', $actorType)->where('created_by_id', $actorId)->count();

        $activityDates = collect([
            $case->diaryEntries->where('authorable_type', $actorType)->where('authorable_id', $actorId)->pluck('created_at'),
            $case->recoveryGoals->flatMap->progressReports->where('reporter_type', $actorType)->where('reporter_id', $actorId)->pluck('reported_at'),
            $case->followups->where('submitted_by_type', $actorType)->where('submitted_by_id', $actorId)->pluck('followed_up_at'),
            $case->homeMonitoringReadings->where('recorded_by_type', $actorType)->where('recorded_by_id', $actorId)->pluck('measured_at'),
            $case->educationAssignments->where('viewed_by_type', $actorType)->where('viewed_by_id', $actorId)->pluck('viewed_at'),
            ($case->dischargeReadinessCheck?->items ?? collect())->where('reviewed_by_type', $actorType)->where('reviewed_by_id', $actorId)->pluck('reviewed_at'),
            $case->caregiverJourneySteps->where('reported_by_type', $actorType)->where('reported_by_id', $actorId)->pluck('reported_at'),
            $case->supportRequests->where('created_by_type', $actorType)->where('created_by_id', $actorId)->pluck('created_at'),
            $passportReportedByActor ? collect([$case->recoveryPassport->reported_at]) : collect(),
        ])->flatten(1);

        $points = $diaryCount * self::POINTS['diary_entry']
            + $goalReportsCount * self::POINTS['goal_progress_report']
            + $wellbeingCount * self::POINTS['wellbeing_self_report']
            + ($passportReportedByActor ? self::POINTS['passport_reported'] : 0)
            + $monitoringCount * self::POINTS['home_monitoring_reading']
            + $educationViewedCount * self::POINTS['education_viewed']
            + $readinessReviewedCount * self::POINTS['discharge_readiness_reviewed']
            + $journeyStepsCount * self::POINTS['caregiver_journey_step']
            + $supportRequestsCount * self::POINTS['support_request_created'];

        $level = intdiv($points, self::POINTS_PER_LEVEL) + 1;
        $xpIntoLevel = $points % self::POINTS_PER_LEVEL;

        $badges = [
            ['icon' => '🖊️', 'label' => 'Cronista', 'unlocked' => $diaryCount >= 1],
            ['icon' => '🎯', 'label' => 'En marcha', 'unlocked' => $goalReportsCount >= 1],
            ['icon' => '💙', 'label' => 'Conectado/a', 'unlocked' => $wellbeingCount >= 1],
            ['icon' => '📋', 'label' => 'Organizado/a', 'unlocked' => $passportReportedByActor],
            ['icon' => '📚', 'label' => 'Estudioso/a', 'unlocked' => $educationViewedCount >= 1],
            ['icon' => '🩺', 'label' => 'Constante', 'unlocked' => $monitoringCount >= 3],
        ];

        if ($canAccessJourney) {
            $badges[] = ['icon' => '🤝', 'label' => 'Acompañante', 'unlocked' => $journeyStepsCount >= 1];
        }

        return [
            'points' => $points,
            'level' => $level,
            'levelTitle' => self::LEVEL_TITLES[min($level, 6)],
            'xpIntoLevel' => $xpIntoLevel,
            'xpForNextLevel' => self::POINTS_PER_LEVEL,
            'xpProgressPct' => round(($xpIntoLevel / self::POINTS_PER_LEVEL) * 100, 1),
            'badges' => $badges,
            'streakDays' => $this->computeStreak($activityDates),
        ];
    }

    /**
     * Días consecutivos (hasta hoy, o hasta ayer si hoy todavía no hay actividad) con
     * al menos una actividad del actor. Igual que los puntos: puramente de interfaz,
     * no se guarda en base de datos.
     */
    private function computeStreak(Collection $dates): int
    {
        $days = $dates->filter()->map(fn ($d) => Carbon::parse($d)->toDateString())->unique();

        if ($days->isEmpty()) {
            return 0;
        }

        $cursor = today();

        if (! $days->contains($cursor->toDateString())) {
            $cursor = $cursor->copy()->subDay();

            if (! $days->contains($cursor->toDateString())) {
                return 0;
            }
        }

        $streak = 0;
        while ($days->contains($cursor->toDateString())) {
            $streak++;
            $cursor = $cursor->copy()->subDay();
        }

        return $streak;
    }

    private function firstName(Patient|Caregiver|null $actor): string
    {
        if ($actor instanceof Patient) {
            return trim(explode(' ', $actor->full_name)[0] ?? $actor->full_name);
        }

        if ($actor instanceof Caregiver) {
            return trim(explode(' ', $actor->name)[0] ?? $actor->name);
        }

        return '';
    }

    public function dismissTour(): RedirectResponse
    {
        $actor = Auth::guard('patient')->user() ?? Auth::guard('caregiver')->user();
        $actor?->update(['has_seen_portal_tour' => true]);

        return redirect()->route('portal.home');
    }

    public static function currentCase(): ?PicsCase
    {
        if ($patient = Auth::guard('patient')->user()) {
            /** @var Patient $patient */
            return CaseAccess::currentCaseForPatient($patient);
        }

        if ($caregiver = Auth::guard('caregiver')->user()) {
            /** @var Caregiver $caregiver */
            return CaseAccess::currentCaseForCaregiver($caregiver);
        }

        return null;
    }
}

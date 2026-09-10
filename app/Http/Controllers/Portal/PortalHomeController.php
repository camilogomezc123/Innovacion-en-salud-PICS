<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Caregiver;
use App\Models\Patient;
use App\Models\PicsCase;
use App\Support\Posuci\CaseAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PortalHomeController extends Controller
{
    public function index(): View
    {
        $case = $this->currentCase();

        if (! $case) {
            return view('portal.home', ['case' => null]);
        }

        $caregiver = Auth::guard('caregiver')->user();
        $canAccessJourney = $caregiver instanceof Caregiver && CaseAccess::caregiverCanAccessJourney($caregiver, $case);

        $case->loadMissing([
            'recoveryGoals', 'educationAssignments.resource', 'dischargeReadinessCheck.items',
            'supportRequests', 'caregiverJourneySteps',
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

        $shortcuts = collect([
            ['title' => 'Antes y ahora', 'description' => 'Tu pasaporte de recuperación.', 'url' => route('portal.passport')],
            ['title' => 'Mi diario', 'description' => 'Lo que tu familia — y tú — han escrito.', 'url' => route('portal.diary')],
            ['title' => 'Mis metas', 'description' => 'Tus metas de recuperación y tu avance.', 'url' => route('portal.goals')],
            ['title' => 'Cómo me siento', 'description' => 'Autorreporte de bienestar.', 'url' => route('portal.wellbeing')],
            ['title' => 'Necesito ayuda', 'description' => 'Reporta una dificultad o una duda.', 'url' => route('portal.support')],
            ['title' => 'Medicamentos', 'description' => 'Los medicamentos conciliados por tu equipo.', 'url' => route('portal.medications')],
            ['title' => 'Monitoreo en casa', 'description' => 'Registra tus lecturas (saturación, presión, etc.).', 'url' => route('portal.home-monitoring')],
            ['title' => 'Preparación para el alta', 'description' => 'Temas clave antes de salir.', 'url' => route('portal.discharge-readiness')],
            ['title' => 'Educación', 'description' => 'Contenido elegido para tu recuperación.', 'url' => route('portal.education')],
        ]);

        if ($canAccessJourney) {
            $shortcuts->push(['title' => 'Mi ruta como cuidador', 'description' => 'Pasos para prepararte a acompañar.', 'url' => route('portal.caregiver-journey')]);
        }

        return view('portal.home', [
            'case' => $case,
            'pendingGoals' => $pendingGoals,
            'unreadEducation' => $unreadEducation,
            'pendingReadinessItems' => $pendingReadinessItems,
            'openSupportRequests' => $openSupportRequests,
            'pendingJourneySteps' => $pendingJourneySteps,
            'shortcuts' => $shortcuts,
        ]);
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

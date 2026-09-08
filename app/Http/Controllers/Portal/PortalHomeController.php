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

        $pendingGoals = $case
            ? $case->recoveryGoals()->where('status', 'active')->count()
            : 0;

        return view('portal.home', [
            'case' => $case,
            'pendingGoals' => $pendingGoals,
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

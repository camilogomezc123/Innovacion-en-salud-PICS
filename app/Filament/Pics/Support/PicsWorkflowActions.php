<?php

namespace App\Filament\Pics\Support;

use App\Enums\CaseStatus;
use App\Filament\Pics\Resources\PicsCases\PicsCaseResource;
use App\Models\PicsCase;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class PicsWorkflowActions
{
    /**
     * @return array<int, Action>
     */
    public static function for(PicsCase $record): array
    {
        return [
            self::recalculateRisk($record),
            self::configurePatientAccess($record),
            self::finalizeFollowup($record),
            self::finalizeReview($record),
            self::reopen($record),
        ];
    }

    private static function configurePatientAccess(PicsCase $record): Action
    {
        return Action::make('configurePatientAccess')
            ->label(fn (): string => $record->patient?->email ? 'Reenviar invitación al paciente' : 'Configurar acceso del paciente')
            ->icon('heroicon-m-key')
            ->color('gray')
            ->schema([
                TextInput::make('email')->label('Correo electrónico del paciente')->email()->required()
                    ->default(fn (): ?string => $record->patient?->email)
                    ->unique('patients', 'email', ignorable: $record->patient),
            ])
            ->requiresConfirmation()
            ->modalDescription('Se generará una contraseña temporal y se enviará por correo al paciente para que ingrese al portal.')
            ->action(function (array $data) use ($record): void {
                $record->patient->update(['email' => $data['email']]);
                $record->patient->sendPortalInvitation();
                Notification::make()->success()->title('Invitación enviada al paciente')->send();
            });
    }

    private static function recalculateRisk(PicsCase $record): Action
    {
        return Action::make('recalculateRisk')
            ->label('Recalcular riesgo')
            ->icon('heroicon-m-calculator')
            ->color('gray')
            ->action(function () use ($record) {
                $record->recalculateRisk()->save();
                Notification::make()->success()
                    ->title('Riesgo recalculado: '.$record->riskLevelLabel().' (puntaje '.$record->risk_score.')')
                    ->send();

                return redirect(PicsCaseResource::getUrl('view', ['record' => $record]));
            });
    }

    private static function finalizeFollowup(PicsCase $record): Action
    {
        return Action::make('finalizeFollowup')
            ->label('Finalizar seguimiento')
            ->icon('heroicon-m-check')
            ->color('success')
            ->visible(function () use ($record): bool {
                $user = auth()->user();

                return $user
                    && $record->status->isAuditorEditable()
                    && ($user->canManagePicsCases() || $record->assigned_auditor_id === $user->id);
            })
            ->requiresConfirmation()
            ->modalDescription('El caso pasará a revisión del líder del programa.')
            ->action(function () use ($record) {
                $record->status = CaseStatus::Pending;
                $record->save();
                Notification::make()->success()->title('Seguimiento finalizado, en espera de revisión')->send();

                return redirect(PicsCaseResource::getUrl('view', ['record' => $record]));
            });
    }

    private static function finalizeReview(PicsCase $record): Action
    {
        return Action::make('finalizeReview')
            ->label('Finalizar revisión')
            ->icon('heroicon-m-shield-check')
            ->color('success')
            ->visible(fn (): bool => (auth()->user()?->canManagePicsCases() ?? false)
                && $record->status === CaseStatus::Pending)
            ->requiresConfirmation()
            ->modalDescription('Se cerrará el caso.')
            ->action(function () use ($record) {
                $record->status = CaseStatus::Completed;
                $record->save();
                Notification::make()->success()->title('Revisión finalizada')->send();

                return redirect(PicsCaseResource::getUrl('view', ['record' => $record]));
            });
    }

    private static function reopen(PicsCase $record): Action
    {
        return Action::make('reopen')
            ->label('Reabrir caso')
            ->icon('heroicon-m-arrow-uturn-left')
            ->color('gray')
            ->visible(fn (): bool => (auth()->user()?->canManagePicsCases() ?? false)
                && in_array($record->status, [CaseStatus::Pending, CaseStatus::Completed], true))
            ->requiresConfirmation()
            ->modalDescription('Uso excepcional: el caso vuelve a "En revisión" para que el responsable pueda corregirlo.')
            ->action(function () use ($record) {
                $record->status = CaseStatus::InReview;
                $record->save();
                Notification::make()->success()->title('Caso reabierto')->send();

                return redirect(PicsCaseResource::getUrl('view', ['record' => $record]));
            });
    }
}

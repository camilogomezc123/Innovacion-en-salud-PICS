<?php

namespace App\Filament\Pics\Resources\PicsCases\Schemas;

use App\Enums\CaseStatus;
use App\Enums\ProgramRole;
use App\Models\PicsCase;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class PicsCaseForm
{
    public static function configure(Schema $schema): Schema
    {
        $managerOnly = fn (): bool => ! (auth()->user()?->canManagePicsCases() ?? false);

        return $schema->components([
            View::make('filament.pics.case-header')->columnSpanFull(),

            Tabs::make('Ruta del caso')
                ->persistTabInQueryString('seccion')
                ->tabs([
                    Tab::make('Resumen')->columns(3)->schema([
                        TextInput::make('case_number')->label('Número de caso')->disabled()->dehydrated(false)
                            ->placeholder('Se asigna automáticamente'),
                        Select::make('status')
                            ->label('Estado')
                            ->options(collect(CaseStatus::cases())->mapWithKeys(fn (CaseStatus $status): array => [$status->value => $status->label()]))
                            ->default(CaseStatus::Assigned->value)
                            ->required()
                            ->disabled($managerOnly)
                            ->helperText('El flujo se controla con las acciones del caso.'),
                        Select::make('assigned_auditor_id')
                            ->label('Responsable de seguimiento')
                            ->relationship('assignedAuditor', 'name', fn ($query) => $query
                                ->where('is_active', true)
                                ->whereHas('programMemberships', fn ($query) => $query
                                    ->where('role', ProgramRole::Auditor->value)
                                    ->where('is_active', true)
                                    ->whereHas('program', fn ($query) => $query
                                        ->whereRaw('upper(code) = ?', ['PICS']))))
                            ->searchable()
                            ->preload()
                            ->disabled($managerOnly),
                        TextInput::make('month')->label('Periodo')->disabled()->dehydrated(false)
                            ->placeholder('Se calcula automáticamente')
                            ->helperText('Se asigna con la fecha de ingreso al programa.'),
                        Select::make('site_id')->label('Sede')->relationship('site', 'name')->searchable()->preload(),
                        Select::make('icu_stay_id')
                            ->label('Estancia UCI de origen')
                            ->relationship('icuStay', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => 'Estancia #'.$record->id.($record->admission_at ? ' · '.$record->admission_at->format('d/m/Y') : ''))
                            ->searchable()
                            ->preload()
                            ->helperText('Opcional. Vincula este seguimiento con la estancia UCI de ICU Liberation cuando exista.'),
                        Select::make('enrollment_source')
                            ->label('Origen del ingreso al programa')
                            ->options(PicsCase::ENROLLMENT_SOURCES),
                        DateTimePicker::make('enrollment_at')->label('Fecha de ingreso al programa')->seconds(false),
                    ]),

                    Tab::make('Completitud')->schema([
                        Placeholder::make('completeness_help')
                            ->label('')
                            ->columnSpanFull()
                            ->content('Un campo vacío se asume "pendiente de diligenciar". Márquelo aquí solo si en realidad no se realizó, no aplica a este caso, o se desconoce.'),
                        Group::make()
                            ->statePath('field_status')
                            ->columns(3)
                            ->schema(collect(PicsCase::KEY_TRACKING_FIELDS)->map(
                                fn (string $label, string $field) => Select::make($field)
                                    ->label($label)
                                    ->options(PicsCase::FIELD_STATUS_OPTIONS)
                                    ->placeholder('Pendiente de diligenciar')
                            )->values()->all()),
                    ]),

                    Tab::make('Riesgo PICS')->columns(3)->schema([
                        TextInput::make('mechanical_ventilation_days')->label('Días de ventilación mecánica')->numeric()->step('0.001')->minValue(0),
                        TextInput::make('delirium_days')->label('Días con delirium (CAM-UCI positivo)')->numeric()->step('0.001')->minValue(0),
                        TextInput::make('icu_los_days')->label('Estancia en UCI (días)')->numeric()->step('0.001')->minValue(0),
                        TextInput::make('sedation_deep_days')->label('Días con sedación profunda')->numeric()->step('0.001')->minValue(0),
                        TextInput::make('age_at_admission')->label('Edad al ingreso UCI (años)')->numeric()->minValue(0)->maxValue(120),
                        TextInput::make('barthel_at_discharge')->label('Índice de Barthel al egreso (0-100)')->numeric()->step('0.1')->minValue(0)->maxValue(100),
                        Toggle::make('shock_or_sepsis')->label('Choque o sepsis en el diagnóstico'),
                        TextInput::make('mrc_total')->label('MRC total (suma 12 grupos musculares, 0-60)')->numeric()->minValue(0)->maxValue(60)
                            ->helperText('< 48 se considera debilidad adquirida en UCI (DAUCI).'),
                        TextInput::make('handgrip_kg')->label('Fuerza de prensión — handgrip (kg, máximo de ambas manos)')->numeric()->step('0.1')->minValue(0)
                            ->helperText('Umbral de alteración: < 16 kg en mujeres, < 27 kg en hombres.'),
                        Placeholder::make('risk_factors_help')
                            ->label('')
                            ->columnSpanFull()
                            ->content('Estos datos alimentan el puntaje de riesgo PICS de 7 factores. Se completan por etapas — usa la acción "Recalcular riesgo" cuando los tengas actualizados.'),
                        Placeholder::make('risk_result')
                            ->label('Resultado del último cálculo')
                            ->columnSpanFull()
                            ->content(fn (?PicsCase $record): string => $record?->risk_level
                                ? $record->riskLevelLabel().' (puntaje '.$record->risk_score.') · '.implode(' · ', $record->risk_factors ?? [])
                                : 'Sin calcular todavía.'),
                    ]),

                    Tab::make('Paciente') ->columns(3)->schema([
                        Select::make('patient_id')
                            ->label('Paciente')
                            ->relationship('patient', 'full_name')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->identification} - {$record->full_name}")
                            ->searchable(['identification', 'full_name'])
                            ->preload()
                            ->required()
                            ->columnSpan(2)
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->helperText(fn (string $operation): ?string => $operation === 'edit'
                                ? 'El paciente no se puede modificar una vez creado el caso.'
                                : null)
                            ->createOptionForm([
                                TextInput::make('identification')->label('Identificación')->required()->maxLength(40),
                                TextInput::make('full_name')->label('Nombre completo')->required()->maxLength(150),
                            ]),
                    ]),

                    Tab::make('Anulación')->columns(3)->schema([
                        Textarea::make('cancellation_reason')->label('Motivo de anulación')->columnSpanFull()
                            ->visible(fn ($get): bool => $get('status') === CaseStatus::Cancelled->value),
                    ]),

                    Tab::make('Historial')->schema([
                        Placeholder::make('history_scope')
                            ->label('Auditoría del caso')
                            ->content('El historial de cambios se consulta en la sección de auditoría del registro.'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}

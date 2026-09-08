<?php

namespace App\Filament\Pics\Resources\PicsCases\Schemas;

use App\Models\PicsCase;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class PicsCaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.pics.case-header')->columnSpanFull(),

            Tabs::make('Ruta del caso')
                ->persistTabInQueryString('seccion')
                ->tabs([
                    Tab::make('Resumen')->columns(3)->schema([
                        TextEntry::make('case_number')->label('Caso')->badge(),
                        TextEntry::make('status')->label('Estado')
                            ->formatStateUsing(fn ($state): string => $state?->label() ?? 'Sin estado')->badge(),
                        TextEntry::make('assignedAuditor.name')->label('Responsable de seguimiento')->placeholder('Sin asignar'),
                        TextEntry::make('month')->label('Periodo')->placeholder('En proceso'),
                        TextEntry::make('site.name')->label('Sede')->placeholder('Sin asignar'),
                        TextEntry::make('icuStay.id')->label('Estancia UCI de origen')->placeholder('Sin vincular')
                            ->formatStateUsing(fn (?string $state): string => $state ? "Estancia #{$state}" : 'Sin vincular'),
                        TextEntry::make('enrollment_source')->label('Origen del ingreso')
                            ->formatStateUsing(fn (?string $state): string => PicsCase::ENROLLMENT_SOURCES[$state] ?? 'Sin dato'),
                        TextEntry::make('enrollment_at')->label('Fecha de ingreso al programa')
                            ->dateTime('d/m/Y H:i')->placeholder('Sin dato'),
                    ]),
                    Tab::make('Completitud')->schema([
                        TextEntry::make('completeness_percentage')
                            ->label('Completitud del registro')
                            ->state(fn (PicsCase $record): string => number_format($record->completenessSummary()['percentage'], 1, ',', '.').'%')
                            ->badge()
                            ->color(fn (PicsCase $record): string => match (true) {
                                $record->completenessSummary()['percentage'] >= 90 => 'success',
                                $record->completenessSummary()['percentage'] >= 60 => 'warning',
                                default => 'danger',
                            }),
                        RepeatableEntry::make('completeness_items')
                            ->label('Detalle por campo')
                            ->state(fn (PicsCase $record): array => $record->completenessSummary()['items'])
                            ->columnSpanFull()
                            ->schema([
                                TextEntry::make('label')->label('Campo')->hiddenLabel(),
                                TextEntry::make('status')->label('Estado')->hiddenLabel()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'filled' => 'Diligenciado',
                                        'not_performed' => 'No realizado',
                                        'not_applicable' => 'No aplica',
                                        'unknown' => 'Desconocido',
                                        default => 'Pendiente de diligenciar',
                                    })
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'filled', 'not_applicable' => 'success',
                                        'pending' => 'warning',
                                        default => 'danger',
                                    }),
                            ])
                            ->columns(2),
                    ]),
                    Tab::make('Factores de riesgo UCI')->columns(3)->schema([
                        TextEntry::make('mechanical_ventilation_days')->label('Días de ventilación mecánica')->placeholder('Sin dato'),
                        TextEntry::make('delirium_days')->label('Días con delirium')->placeholder('Sin dato'),
                        TextEntry::make('icu_los_days')->label('Estancia en UCI (días)')->placeholder('Sin dato'),
                        TextEntry::make('sedation_deep_days')->label('Días con sedación profunda')->placeholder('Sin dato'),
                    ]),
                    Tab::make('Paciente')->columns(3)->schema([
                        TextEntry::make('patient.full_name')->label('Paciente'),
                        TextEntry::make('patient.identification')->label('Identificación'),
                    ]),
                    Tab::make('Historial')->schema([
                        TextEntry::make('history_scope')->label('Auditoría del caso')
                            ->state('El historial se consulta en la sección de auditoría del registro.'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}

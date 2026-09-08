<?php

namespace App\Filament\Pics\Resources\PicsCases\RelationManagers;

use App\Models\PicsFollowup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FollowupsRelationManager extends RelationManager
{
    protected static string $relationship = 'followups';

    protected static ?string $title = 'Seguimientos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Seguimiento')->columnSpanFull()->tabs([
                Tab::make('Contacto')->columns(3)->schema([
                    Select::make('checkpoint')->label('Checkpoint')->options(PicsFollowup::CHECKPOINTS)->required(),
                    Select::make('contact_method')->label('Método de contacto')->options([
                        'presencial' => 'Presencial', 'telefonico' => 'Telefónico', 'telemedicina' => 'Telemedicina',
                    ]),
                    Toggle::make('contact_achieved')->label('Contacto logrado'),
                    DateTimePicker::make('followed_up_at')->label('Fecha de seguimiento')->seconds(false),
                    Select::make('responsible_user_id')->label('Responsable')->relationship('responsible', 'name')->searchable()->preload(),
                ]),
                Tab::make('Función física')->columns(3)->schema([
                    TextInput::make('functional_capacity')->label('Capacidad funcional'),
                    TextInput::make('mobility')->label('Movilidad'),
                    TextInput::make('strength')->label('Fuerza'),
                    TextInput::make('fatigue')->label('Fatiga'),
                    TextInput::make('pain')->label('Dolor'),
                    TextInput::make('sleep_quality')->label('Calidad del sueño'),
                ]),
                Tab::make('Cognición y salud mental')->columns(3)->schema([
                    TextInput::make('cognition_tool')->label('Instrumento de cognición'),
                    TextInput::make('cognition_result')->label('Resultado de cognición'),
                    Toggle::make('cognition_positive')->label('Tamizaje cognitivo positivo'),
                    TextInput::make('anxiety_tool')->label('Instrumento de ansiedad'),
                    TextInput::make('anxiety_result')->label('Resultado de ansiedad'),
                    Toggle::make('anxiety_positive')->label('Tamizaje de ansiedad positivo'),
                    TextInput::make('depression_tool')->label('Instrumento de depresión'),
                    TextInput::make('depression_result')->label('Resultado de depresión'),
                    Toggle::make('depression_positive')->label('Tamizaje de depresión positivo'),
                    TextInput::make('ptsd_tool')->label('Instrumento de TEPT'),
                    TextInput::make('ptsd_result')->label('Resultado de TEPT'),
                    Toggle::make('ptsd_positive')->label('Tamizaje de TEPT positivo'),
                ]),
                Tab::make('Resultado')->columns(3)->schema([
                    Toggle::make('readmission')->label('Reingreso'),
                    Toggle::make('return_to_work')->label('Retorno laboral'),
                    Textarea::make('medications_review')->label('Conciliación de medicamentos')->columnSpanFull(),
                    TextInput::make('quality_of_life_tool')->label('Instrumento de calidad de vida'),
                    TextInput::make('quality_of_life_result')->label('Resultado de calidad de vida'),
                    TextInput::make('caregiver_burden_tool')->label('Instrumento de sobrecarga del cuidador'),
                    TextInput::make('caregiver_burden_result')->label('Resultado de sobrecarga del cuidador'),
                    Textarea::make('notes')->label('Notas')->columnSpanFull(),
                ]),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('checkpoint')->label('Checkpoint')
                    ->formatStateUsing(fn (string $state): string => PicsFollowup::CHECKPOINTS[$state] ?? $state)
                    ->badge(),
                TextColumn::make('followed_up_at')->label('Fecha')->dateTime('d/m/Y H:i')->placeholder('—'),
                IconColumn::make('contact_achieved')->label('Contacto logrado')->boolean(),
                IconColumn::make('cognition_positive')->label('Cognición +')->boolean(),
                IconColumn::make('anxiety_positive')->label('Ansiedad +')->boolean(),
                IconColumn::make('depression_positive')->label('Depresión +')->boolean(),
                IconColumn::make('ptsd_positive')->label('TEPT +')->boolean(),
                IconColumn::make('readmission')->label('Reingreso')->boolean(),
            ])
            ->defaultSort('followed_up_at')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()]);
    }
}

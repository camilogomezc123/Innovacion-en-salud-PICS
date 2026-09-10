<?php

namespace App\Filament\Pics\Resources\PicsCases\RelationManagers;

use App\Models\EducationAssignment;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EducationAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'educationAssignments';

    protected static ?string $title = 'Educación personalizada';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('education_resource_id')
                ->label('Contenido')
                ->relationship('resource', 'title', fn ($query) => $query->where('is_active', true))
                ->searchable()->preload()->required(),
            Textarea::make('notes')->label('Por qué aplica a este paciente (opcional)')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('resource.title')->label('Contenido')->searchable(),
                TextColumn::make('resource.category')->label('Categoría')
                    ->formatStateUsing(fn (EducationAssignment $record): string => $record->resource?->categoryLabel() ?? '—'),
                TextColumn::make('assignedBy.name')->label('Asignado por')->placeholder('—'),
                IconColumn::make('viewed_at')->label('Leído')->boolean()
                    ->getStateUsing(fn (EducationAssignment $record): bool => $record->isViewed()),
            ])
            ->defaultSort('assigned_at', 'desc')
            ->headerActions([
                CreateAction::make()->mutateFormDataUsing(function (array $data): array {
                    $data['assigned_by'] = auth('web')->id();
                    $data['assigned_at'] = now();

                    return $data;
                }),
            ])
            ->recordActions([EditAction::make()]);
    }
}

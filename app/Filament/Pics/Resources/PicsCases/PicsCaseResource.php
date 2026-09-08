<?php

namespace App\Filament\Pics\Resources\PicsCases;

use App\Enums\ProgramRole;
use App\Filament\Pics\Resources\PicsCases\Pages\CreatePicsCase;
use App\Filament\Pics\Resources\PicsCases\Pages\EditPicsCase;
use App\Filament\Pics\Resources\PicsCases\Pages\ListPicsCases;
use App\Filament\Pics\Resources\PicsCases\Pages\ViewPicsCase;
use App\Filament\Pics\Resources\PicsCases\RelationManagers\ClinicalAuditsRelationManager;
use App\Filament\Pics\Resources\PicsCases\RelationManagers\FollowupsRelationManager;
use App\Filament\Pics\Resources\PicsCases\RelationManagers\ReferralsRelationManager;
use App\Filament\Pics\Resources\PicsCases\Schemas\PicsCaseForm;
use App\Filament\Pics\Resources\PicsCases\Schemas\PicsCaseInfolist;
use App\Filament\Pics\Resources\PicsCases\Tables\PicsCasesTable;
use App\Models\PicsCase;
use App\Support\ProgramAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PicsCaseResource extends Resource
{
    protected static ?string $model = PicsCase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?string $navigationLabel = 'Casos';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'caso PICS';

    protected static ?string $pluralModelLabel = 'Casos PICS';

    protected static ?string $recordTitleAttribute = 'case_number';

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update', $record) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', PicsCase::class) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->whereHas(
            'program',
            fn (Builder $query) => $query->whereRaw('upper(code) = ?', ['PICS']),
        );
        $user = auth()->user();

        if ($user && ProgramAccess::hasRole($user, 'pics', ProgramRole::Auditor)) {
            $query->where('assigned_auditor_id', $user->id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return PicsCaseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PicsCaseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PicsCasesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPicsCases::route('/'),
            'create' => CreatePicsCase::route('/create'),
            'view' => ViewPicsCase::route('/{record}'),
            'edit' => EditPicsCase::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            FollowupsRelationManager::class,
            ReferralsRelationManager::class,
            ClinicalAuditsRelationManager::class,
        ];
    }
}

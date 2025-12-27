<?php

namespace App\Filament\Student\Resources;

use BackedEnum;
use App\Filament\Student\Resources\CertificateResource\Pages;
use App\Models\Certificate;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Certificates';

    protected static ?int $navigationSort = 60;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('user_id', Auth::id());
            })
            ->columns([
                Tables\Columns\TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('certificate_number')
                    ->label('Certificate Number')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('monospace'),
                Tables\Columns\TextColumn::make('issued_at')
                    ->label('Issued Date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('verification_link')
                    ->label('Verification')
                    ->formatStateUsing(fn (Certificate $record) => 'View')
                    ->url(fn (Certificate $record) => $record->getVerificationUrl())
                    ->openUrlInNewTab(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Course')
                    ->relationship('course', 'title')
                    ->preload(),
            ])
            ->defaultSort('issued_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificates::route('/'),
        ];
    }
}

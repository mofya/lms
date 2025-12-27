<?php

namespace App\Filament\Resources;

use BackedEnum;
use UnitEnum;
use App\Filament\Resources\AssignmentResource\Pages;
use App\Filament\Resources\SubmissionResource;
use App\Models\Assignment;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static UnitEnum|string|null $navigationGroup = 'Course Content';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'text' => 'Text Submission',
                                'file' => 'File Upload',
                                'code' => 'Code Submission',
                            ])
                            ->required()
                            ->default('text')
                            ->reactive()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('max_points')
                            ->label('Maximum Points')
                            ->numeric()
                            ->default(100)
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Checkbox::make('is_published')
                            ->label('Published'),
                    ]),

                Section::make('Instructions')
                    ->schema([
                        RichEditor::make('instructions')
                            ->label('Detailed Instructions')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'link',
                                'codeBlock',
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Submission Settings')
                    ->schema([
                        Forms\Components\TextInput::make('max_submissions')
                            ->label('Maximum Submissions')
                            ->helperText('Set to 0 for unlimited submissions until due date')
                            ->numeric()
                            ->default(1)
                            ->minValue(0)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('max_file_size_mb')
                            ->label('Maximum File Size (MB)')
                            ->numeric()
                            ->default(10)
                            ->minValue(1)
                            ->visible(fn (callable $get) => in_array($get('type'), ['file', 'code']))
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('allowed_file_types')
                            ->label('Allowed File Types')
                            ->helperText('e.g., pdf, docx, py, js, txt (leave empty for all types)')
                            ->placeholder('Add file extension')
                            ->visible(fn (callable $get) => in_array($get('type'), ['file', 'code']))
                            ->columnSpanFull(),
                    ]),

                Section::make('Scheduling')
                    ->schema([
                        Forms\Components\DateTimePicker::make('available_from')
                            ->label('Available From')
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('due_at')
                            ->label('Due Date')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('late_due_at')
                            ->label('Late Submission Deadline (Grace Period)')
                            ->helperText('Optional: Allow late submissions until this date')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('late_penalty_percent')
                            ->label('Late Penalty (%)')
                            ->helperText('Percentage to deduct per day after due date (e.g., 10 = -10% per day)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->columnSpanFull(),
                    ]),

                Section::make('Course Assignment')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->relationship('course', 'title')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Rubric')
                    ->schema([
                        Forms\Components\Select::make('rubric.type')
                            ->label('Rubric Type')
                            ->options([
                                'freeform' => 'Free-form Text',
                                'structured' => 'Structured Criteria',
                            ])
                            ->default('freeform')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'freeform') {
                                    $set('rubric.criteria', []);
                                }
                            })
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('rubric.freeform_text')
                            ->label('Rubric Description')
                            ->helperText('Describe the grading criteria and expectations')
                            ->rows(5)
                            ->visible(fn (callable $get) => $get('rubric.type') === 'freeform')
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('rubric.criteria')
                            ->label('Rubric Criteria')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Criterion Name')
                                    ->required()
                                    ->placeholder('e.g., Grammar, Content Quality')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('max_points')
                                    ->label('Points')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->columnSpan(1),
                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->visible(fn (callable $get) => $get('rubric.type') === 'structured')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course.title')
                    ->label('Course')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'text' => 'info',
                        'file' => 'warning',
                        'code' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('due_at')
                    ->label('Due Date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Submissions')
                    ->counts('submissions')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_published')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'text' => 'Text',
                        'file' => 'File',
                        'code' => 'Code',
                    ]),
                Tables\Filters\SelectFilter::make('course_id')
                    ->relationship('course', 'title'),
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Published'),
            ])
            ->recordActions([
                Action::make('view_submissions')
                    ->label('View Submissions')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Assignment $record) => SubmissionResource::getUrl('index', ['assignment_id' => $record->id])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('due_at', 'desc');
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
            'index' => Pages\ListAssignments::route('/'),
            'create' => Pages\CreateAssignment::route('/create'),
            'edit' => Pages\EditAssignment::route('/{record}/edit'),
        ];
    }
}

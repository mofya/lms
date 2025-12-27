<?php

namespace App\Filament\Student\Resources\DiscussionResource\Pages;

use App\Filament\Student\Resources\DiscussionResource;
use App\Models\DiscussionReply;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class ViewDiscussion extends ViewRecord implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = DiscussionResource::class;

    public ?array $replyData = [];
    public ?int $parentReplyId = null;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->form->fill();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pin')
                ->label(fn() => $this->record->is_pinned ? 'Unpin' : 'Pin')
                ->icon('heroicon-o-paper-clip')
                ->visible(fn() => $this->record->course->lecturer_id === auth()->id() || auth()->user()->is_admin)
                ->action(function () {
                    $this->record->update(['is_pinned' => !$this->record->is_pinned]);
                }),
            Actions\Action::make('lock')
                ->label(fn() => $this->record->is_locked ? 'Unlock' : 'Lock')
                ->icon('heroicon-o-lock-closed')
                ->visible(fn() => $this->record->course->lecturer_id === auth()->id() || auth()->user()->is_admin)
                ->action(function () {
                    $this->record->update(['is_locked' => !$this->record->is_locked]);
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Post a Reply')
                    ->schema([
                        RichEditor::make('content')
                            ->label('Your Reply')
                            ->required()
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
                    ])
                    ->visible(fn() => !$this->record->is_locked)
                    ->collapsible(),
            ])
            ->statePath('replyData');
    }

    public function submitReply(): void
    {
        if ($this->record->is_locked) {
            \Filament\Notifications\Notification::make()
                ->title('This discussion is locked')
                ->warning()
                ->send();
            return;
        }

        $data = $this->form->getState();
        
        $reply = DiscussionReply::create([
            'discussion_id' => $this->record->id,
            'user_id' => auth()->id(),
            'parent_id' => $this->parentReplyId,
            'content' => $data['content'],
        ]);

        $reply->incrementDiscussionRepliesCount();
        
        // Award XP for discussion participation
        (new \App\Services\XpService())->awardDiscussionParticipation(auth()->user());
        (new \App\Services\XpService())->updateStreak(auth()->user());
        
        $this->form->fill();
        $this->parentReplyId = null;
        
        \Filament\Notifications\Notification::make()
            ->title('Reply posted')
            ->success()
            ->send();
    }

    public function replyToReply(int $replyId): void
    {
        $this->parentReplyId = $replyId;
        $this->form->fill();
    }

    public function markBestAnswer(int $replyId): void
    {
        if ($this->record->course->lecturer_id !== auth()->id() && !auth()->user()->is_admin) {
            return;
        }

        $reply = DiscussionReply::findOrFail($replyId);
        $this->record->markBestAnswer($reply);
        
        \Filament\Notifications\Notification::make()
            ->title('Marked as best answer')
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        return [
            'replies' => $this->record->replies()->with(['user', 'replies.user'])->get(),
        ];
    }
}

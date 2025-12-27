<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h2 class="text-2xl font-bold">{{ $this->record->title }}</h2>
                    <p class="text-gray-600 mt-1">
                        by {{ $this->record->user->name }} in {{ $this->record->course->title }}
                        <span class="text-sm text-gray-400">{{ $this->record->created_at->diffForHumans() }}</span>
                    </p>
                </div>
                @if($this->record->is_pinned)
                    <span class="badge badge-info">Pinned</span>
                @endif
                @if($this->record->is_locked)
                    <span class="badge badge-warning">Locked</span>
                @endif
            </div>
            
            <div class="prose max-w-none">
                {!! $this->record->content !!}
            </div>
        </div>

        @if(!$this->record->is_locked)
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Post a Reply</h3>
                {{ $this->form }}
                
                <div class="mt-4">
                    <x-filament::button wire:click="submitReply">
                        Post Reply
                    </x-filament::button>
                </div>
            </div>
        @endif

        <div class="space-y-4">
            <h3 class="text-lg font-semibold">Replies ({{ $this->record->replies_count }})</h3>
            
            @foreach($this->getViewData()['replies'] as $reply)
                <div class="bg-white rounded-lg shadow p-6 {{ $reply->is_best_answer ? 'border-2 border-success' : '' }}">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <span class="font-semibold">{{ $reply->user->name }}</span>
                            <span class="text-sm text-gray-400 ml-2">{{ $reply->created_at->diffForHumans() }}</span>
                            @if($reply->is_best_answer)
                                <span class="badge badge-success ml-2">Best Answer</span>
                            @endif
                        </div>
                        @if($this->record->course->lecturer_id === auth()->id() || auth()->user()->is_admin)
                            @if(!$reply->is_best_answer)
                                <x-filament::button size="sm" wire:click="markBestAnswer({{ $reply->id }})">
                                    Mark as Best Answer
                                </x-filament::button>
                            @endif
                        @endif
                    </div>
                    
                    <div class="prose max-w-none mb-4">
                        {!! $reply->content !!}
                    </div>

                    @if(!$this->record->is_locked)
                        <x-filament::button size="sm" wire:click="replyToReply({{ $reply->id }})">
                            Reply
                        </x-filament::button>
                    @endif

                    @if($reply->replies->isNotEmpty())
                        <div class="mt-4 ml-8 space-y-4 border-l-2 border-gray-200 pl-4">
                            @foreach($reply->replies as $nestedReply)
                                <div>
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <span class="font-semibold">{{ $nestedReply->user->name }}</span>
                                            <span class="text-sm text-gray-400 ml-2">{{ $nestedReply->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                    <div class="prose max-w-none">
                                        {!! $nestedReply->content !!}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            @if($this->record->replies_count === 0)
                <div class="text-center py-8 text-gray-400">
                    <p>No replies yet. Be the first to reply!</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>

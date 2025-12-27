<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            <h2 class="text-2xl font-bold">AI Study Assistant</h2>
            <p class="text-gray-600 mt-1">Ask questions about your courses and get AI-powered explanations</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Ask a Question</h3>
                {{ $this->form }}
                
                <div class="mt-4">
                    <x-filament::button wire:click="askQuestion" wire:loading.attr="disabled">
                        <span wire:loading.remove>Ask Question</span>
                        <span wire:loading>Processing...</span>
                    </x-filament::button>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Response</h3>
                
                @if($this->isLoading)
                    <div class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
                        <span class="ml-3 text-gray-600">Thinking...</span>
                    </div>
                @elseif($this->response)
                    <div class="prose max-w-none">
                        <div class="whitespace-pre-wrap text-gray-700">{{ $this->response }}</div>
                    </div>
                @else
                    <div class="text-gray-400 text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="mt-2">Your response will appear here</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>

<?php

namespace App\Livewire\WhatsApp;

use App\Models\WhatsAppConversation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

class WhatsAppConsolePage extends Component
{
    #[Url(as: 'conversation', except: null)]
    public ?int $selectedConversationId = null;

    #[Url(as: 'search', except: '')]
    public string $search = '';

    public bool $unreadOnly = false;

    #[Layout('layouts.app')]
    public function render()
    {
        $conversations = WhatsAppConversation::query()
            ->with(['contact.user', 'latestMessage'])
            ->when($this->search !== '', function ($query) {
                $query->whereHas('contact', function ($contactQuery) {
                    $contactQuery
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('phone', 'like', '%'.$this->search.'%')
                        ->orWhere('normalized_phone', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->unreadOnly, fn ($query) => $query->whereHas('contact', fn ($contactQuery) => $contactQuery->where('unread_count', '>', 0)))
            ->orderByDesc('last_message_at')
            ->limit(50)
            ->get();

        if (! $this->selectedConversationId && $conversations->isNotEmpty()) {
            $this->selectedConversationId = $conversations->first()->id;
        }

        $selectedConversation = $this->selectedConversationId
            ? WhatsAppConversation::query()
                ->with(['contact.user', 'messages.statuses'])
                ->find($this->selectedConversationId)
            : null;

        return view('livewire.whatsapp.console-page', [
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
            'selectedMessages' => $selectedConversation?->messages()->latest()->take(100)->get()->reverse()->values() ?? collect(),
        ]);
    }

    public function selectConversation(int $conversationId): void
    {
        $this->selectedConversationId = $conversationId;

        $conversation = WhatsAppConversation::query()
            ->with('contact')
            ->find($conversationId);

        if (! $conversation) {
            return;
        }

        $conversation->contact->forceFill([
            'unread_count' => 0,
        ])->save();
    }
}

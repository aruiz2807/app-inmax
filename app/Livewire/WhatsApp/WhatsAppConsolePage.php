<?php

namespace App\Livewire\WhatsApp;

use App\Models\User;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppSetting;
use App\Models\WhatsAppWebhookEvent;
use App\Services\WhatsApp\WhatsAppCloudApiService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

class WhatsAppConsolePage extends Component
{
    private const STATUS_FILTERS = ['all', 'open', 'archived'];
    private const LINKED_FILTERS = ['all', 'prospects', 'users'];

    #[Url(as: 'conversation', except: null)]
    public ?int $selectedConversationId = null;

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: 'all')]
    public string $statusFilter = 'all';

    #[Url(as: 'linked', except: 'all')]
    public string $linkedFilter = 'all';

    public bool $unreadOnly = false;

    public ?string $assignedUserId = null;
    public string $replyMessage = '';

    #[Layout('layouts.app')]
    public function render()
    {
        $this->statusFilter = in_array($this->statusFilter, self::STATUS_FILTERS, true)
            ? $this->statusFilter
            : 'all';

        $this->linkedFilter = in_array($this->linkedFilter, self::LINKED_FILTERS, true)
            ? $this->linkedFilter
            : 'all';

        $conversations = WhatsAppConversation::query()
            ->with(['contact.user', 'latestMessage', 'assignedUser'])
            ->when($this->search !== '', function (Builder $query) {
                $query->where(function (Builder $conversationQuery) {
                    $conversationQuery
                        ->whereHas('contact', function (Builder $contactQuery) {
                            $contactQuery
                                ->where('name', 'like', '%'.$this->search.'%')
                                ->orWhere('phone', 'like', '%'.$this->search.'%')
                                ->orWhere('normalized_phone', 'like', '%'.$this->search.'%');
                        })
                        ->orWhereHas('assignedUser', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->unreadOnly, fn (Builder $query) => $query->whereHas('contact', fn (Builder $contactQuery) => $contactQuery->where('unread_count', '>', 0)))
            ->when($this->statusFilter === 'open', fn (Builder $query) => $query->where('status', 'open'))
            ->when($this->statusFilter === 'archived', fn (Builder $query) => $query->where('status', 'archived'))
            ->when($this->linkedFilter === 'prospects', fn (Builder $query) => $query->whereHas('contact', fn (Builder $contactQuery) => $contactQuery->whereNull('user_id')))
            ->when($this->linkedFilter === 'users', fn (Builder $query) => $query->whereHas('contact', fn (Builder $contactQuery) => $contactQuery->whereNotNull('user_id')))
            ->orderByDesc('last_message_at')
            ->limit(50)
            ->get();

        if ($this->selectedConversationId && ! $conversations->pluck('id')->contains($this->selectedConversationId)) {
            $this->selectedConversationId = $conversations->first()?->id;
        }

        if (! $this->selectedConversationId && $conversations->isNotEmpty()) {
            $this->selectedConversationId = $conversations->first()->id;
        }

        $selectedConversation = $this->selectedConversationId
            ? WhatsAppConversation::query()
                ->with(['contact.user', 'assignedUser', 'messages.statuses'])
                ->find($this->selectedConversationId)
            : null;

        $this->assignedUserId = $selectedConversation?->assigned_user_id
            ? (string) $selectedConversation->assigned_user_id
            : null;

        $summary = [
            'total_conversations' => WhatsAppConversation::query()->count(),
            'unread_messages' => WhatsAppContact::query()->sum('unread_count'),
            'prospect_conversations' => WhatsAppConversation::query()
                ->whereHas('contact', fn (Builder $query) => $query->whereNull('user_id'))
                ->count(),
            'messages_today' => WhatsAppMessage::query()
                ->whereDate('created_at', today())
                ->count(),
        ];

        $assignableUsers = User::query()
            ->whereIn('profile', ['Admin', 'Sales'])
            ->orderBy('name')
            ->get(['id', 'name', 'profile']);

        $settings = WhatsAppSetting::query()->first();
        $webhookEvents = WhatsAppWebhookEvent::query()
            ->latest()
            ->limit(8)
            ->get();

        return view('livewire.whatsapp.console-page', [
            'assignableUsers' => $assignableUsers,
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
            'selectedMessages' => $selectedConversation?->messages()->latest()->take(100)->get()->reverse()->values() ?? collect(),
            'summary' => $summary,
            'webhookEvents' => $webhookEvents,
            'webhookSettings' => $settings,
        ]);
    }

    public function selectConversation(int $conversationId): void
    {
        $this->selectedConversationId = $conversationId;
        $this->replyMessage = '';

        $conversation = WhatsAppConversation::query()
            ->with('contact')
            ->find($conversationId);

        if (! $conversation) {
            $this->assignedUserId = null;
            return;
        }

        $this->assignedUserId = $conversation->assigned_user_id
            ? (string) $conversation->assigned_user_id
            : null;

        $this->markConversationAsRead($conversation);
    }

    public function updatedAssignedUserId(?string $value): void
    {
        if (! $this->selectedConversationId) {
            return;
        }

        $assigneeId = filled($value) ? (int) $value : null;

        if ($assigneeId !== null && ! User::query()->whereKey($assigneeId)->whereIn('profile', ['Admin', 'Sales'])->exists()) {
            $this->dispatch(
                'notify',
                type: 'error',
                content: 'Solo se puede asignar a usuarios Admin o Sales.',
                duration: 4000
            );

            $this->assignedUserId = null;

            return;
        }

        WhatsAppConversation::query()
            ->whereKey($this->selectedConversationId)
            ->update([
                'assigned_user_id' => $assigneeId,
            ]);

        $message = $assigneeId
            ? 'Conversación asignada correctamente.'
            : 'Asignación removida correctamente.';

        $this->dispatch(
            'notify',
            type: 'success',
            content: $message,
            duration: 3000
        );
    }

    public function archiveSelectedConversation(): void
    {
        if (! $this->selectedConversationId) {
            return;
        }

        WhatsAppConversation::query()
            ->whereKey($this->selectedConversationId)
            ->update([
                'status' => 'archived',
                'archived_at' => now(),
            ]);

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Conversación archivada.',
            duration: 3000
        );
    }

    public function reopenSelectedConversation(): void
    {
        if (! $this->selectedConversationId) {
            return;
        }

        WhatsAppConversation::query()
            ->whereKey($this->selectedConversationId)
            ->update([
                'status' => 'open',
                'archived_at' => null,
            ]);

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Conversación reabierta.',
            duration: 3000
        );
    }

    public function markSelectedConversationAsRead(): void
    {
        if (! $this->selectedConversationId) {
            return;
        }

        $conversation = WhatsAppConversation::query()
            ->with('contact')
            ->find($this->selectedConversationId);

        if (! $conversation) {
            return;
        }

        $this->markConversationAsRead($conversation);
    }

    public function refreshConsole(): void
    {
        // Triggered by wire:poll to keep the console in sync with webhook traffic.
    }

    public function sendReply(WhatsAppCloudApiService $service): void
    {
        Validator::make([
            'replyMessage' => $this->replyMessage,
        ], [
            'replyMessage' => ['required', 'string', 'max:4096'],
        ], [
            'replyMessage.required' => 'Escribe un mensaje antes de enviar.',
        ])->validate();

        if (! $this->selectedConversationId) {
            $this->dispatch(
                'notify',
                type: 'error',
                content: 'Selecciona una conversación antes de responder.',
                duration: 4000
            );

            return;
        }

        $conversation = WhatsAppConversation::query()
            ->with('contact')
            ->find($this->selectedConversationId);

        if (! $conversation || ! $conversation->contact) {
            $this->dispatch(
                'notify',
                type: 'error',
                content: 'No se encontró la conversación seleccionada.',
                duration: 4000
            );

            return;
        }

        $phone = $conversation->contact->wa_id
            ?: $conversation->contact->phone
            ?: $conversation->contact->normalized_phone;

        if (! filled($phone)) {
            $this->dispatch(
                'notify',
                type: 'error',
                content: 'La conversación no tiene un número de teléfono válido.',
                duration: 4000
            );

            return;
        }

        $setting = WhatsAppSetting::query()->first();

        if (! $setting || ! filled($setting->access_token) || ! filled($setting->phone_number_id)) {
            $this->dispatch(
                'notify',
                type: 'error',
                content: 'Primero configura Access Token e ID de línea en WhatsApp.',
                duration: 5000
            );

            return;
        }

        $body = trim($this->replyMessage);
        $result = $service->sendTextMessage(
            setting: $setting,
            to: $phone,
            body: $body,
        );

        if ($result['ok']) {
            $this->replyMessage = '';

            $this->dispatch(
                'notify',
                type: 'success',
                content: 'Mensaje enviado correctamente.',
                duration: 3000
            );

            return;
        }

        $errorMessage = data_get(
            $result['data'],
            'error.message',
            'No fue posible enviar el mensaje de WhatsApp.'
        );

        $this->dispatch(
            'notify',
            type: 'error',
            content: $errorMessage,
            duration: 6000
        );
    }

    private function markConversationAsRead(WhatsAppConversation $conversation): void
    {
        $conversation->contact->forceFill([
            'unread_count' => 0,
        ])->save();
    }
}

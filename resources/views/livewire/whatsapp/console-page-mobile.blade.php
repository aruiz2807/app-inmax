<div wire:poll.15s="refreshConsole" class="flex h-[calc(100dvh-7rem)] min-h-0 w-full min-w-0 flex-col overflow-hidden">
    <x-slot name="header">
        {{ __('app.whatsapp_console') }}
    </x-slot>

    @if (! $selectedConversation)
        <div class="flex min-h-0 flex-1 flex-col gap-2">
            <div class="flex shrink-0 items-center justify-between px-1 text-xs text-slate-500">
                <span>{{ $summary['total_conversations'] }} conversaciones</span>
                <span>{{ $summary['unread_messages'] }} no leídos</span>
            </div>

            <div class="shrink-0 rounded-2xl border border-slate-200 bg-white p-3">
                <div class="space-y-2">
                    <input wire:model.live.debounce.300ms="search" type="text"
                        aria-label="Buscar conversación"
                        placeholder="Buscar por nombre o teléfono..."
                        class="w-full rounded-box border border-black/10 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm transition-colors focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-50 dark:focus:border-white/20 dark:focus:ring-neutral-100/15" />

                    <div class="grid grid-cols-2 gap-2">
                        <select wire:model.live="statusFilter" aria-label="Estatus"
                            class="w-full rounded-box border border-black/10 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm transition-colors focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-50 dark:focus:border-white/20 dark:focus:ring-neutral-100/15">
                            <option value="all">Todos</option>
                            <option value="open">Abiertos</option>
                            <option value="archived">Archivados</option>
                        </select>

                        <select wire:model.live="linkedFilter" aria-label="Tipo de contacto"
                            class="w-full rounded-box border border-black/10 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm transition-colors focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-50 dark:focus:border-white/20 dark:focus:ring-neutral-100/15">
                            <option value="all">Todos</option>
                            <option value="prospects">Prospectos</option>
                            <option value="users">Usuarios vinculados</option>
                        </select>
                    </div>

                    <label class="flex items-center justify-between gap-3 px-1 text-sm text-slate-600">
                        <span class="font-medium text-slate-900">Solo no leídos</span>
                        <x-ui.switch
                            wire:key="mobile-unread-only-switch-{{ $unreadOnly ? '1' : '0' }}"
                            wire:model.live="unreadOnly"
                            :checked="$unreadOnly"
                            color="teal"
                        />
                    </label>
                </div>
            </div>

            <div class="min-h-0 w-full flex-1 overflow-y-auto rounded-2xl border border-slate-200 bg-slate-50/70 p-2">
            @forelse ($conversations as $conversation)
                @php
                    $contact = $conversation->contact;
                    $lastMessage = $conversation->latestMessage;
                @endphp

                <div wire:key="mobile-conversation-list-item-{{ $conversation->id }}"
                    wire:click="selectConversation({{ $conversation->id }})"
                    wire:keydown.enter="selectConversation({{ $conversation->id }})"
                    wire:keydown.space.prevent="selectConversation({{ $conversation->id }})"
                    role="button" tabindex="0"
                    class="mb-2 w-full cursor-pointer rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-teal-300 focus:outline-none focus:ring-2 focus:ring-teal-200">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">
                                {{ $contact->user?->name ?? ($contact->name ?? 'Sin nombre') }}
                            </p>
                            <p class="truncate text-xs text-slate-500">
                                {{ $contact->phone ?? $contact->normalized_phone }}
                            </p>
                        </div>

                        <div class="flex shrink-0 flex-col items-end gap-2 text-right">
                            @if ($conversation->last_message_at)
                                <span class="text-[11px] text-slate-400">{{ $conversation->last_message_at->format('d/m H:i') }}</span>
                            @endif
                            @if ($contact->unread_count > 0)
                                <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-teal-500 px-2 py-1 text-[11px] font-semibold text-white">
                                    {{ $contact->unread_count }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2 text-[11px]">
                        @if ($contact->user)
                            <span class="rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 font-medium text-slate-600">
                                {{ $contact->user->profile }}
                            </span>
                        @else
                            <span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 font-medium text-amber-700">Prospecto</span>
                        @endif

                        @if ($conversation->status === 'archived')
                            <span class="rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 font-medium text-rose-700">Archivado</span>
                        @endif
                    </div>

                    <p class="mt-3 truncate text-xs leading-5 text-slate-500">
                        {{ $lastMessage?->body_text ?? 'Sin mensajes aún' }}
                    </p>
                </div>
            @empty
                <div class="flex min-h-40 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">
                    No hay conversaciones registradas con los filtros actuales.
                </div>
            @endforelse
            </div>
        </div>
    @else
        <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="border-b border-slate-200 p-2">
                <x-ui.button type="button" icon="arrow-left" variant="outline" color="teal" wire:click="showConversationList">
                    Volver a conversaciones
                </x-ui.button>
            </div>

            @include('livewire.whatsapp.conversation-detail-panel')
        </div>
    @endif
</div>
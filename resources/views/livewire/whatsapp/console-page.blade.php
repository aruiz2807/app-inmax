<div wire:poll.15s="refreshConsole" class="space-y-4">
    <x-slot name="header">
        {{ __('app.whatsapp_console') }}
    </x-slot>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.card size="full">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Conversaciones</p>
            <p class="pt-2 text-3xl font-semibold text-slate-900">{{ $summary['total_conversations'] }}</p>
            <p class="pt-1 text-sm text-slate-500">Bandeja total persistida.</p>
        </x-ui.card>

        <x-ui.card size="full">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">No leídos</p>
            <p class="pt-2 text-3xl font-semibold text-teal-700">{{ $summary['unread_messages'] }}</p>
            <p class="pt-1 text-sm text-slate-500">Mensajes pendientes por revisar.</p>
        </x-ui.card>

        <x-ui.card size="full">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Prospectos</p>
            <p class="pt-2 text-3xl font-semibold text-amber-600">{{ $summary['prospect_conversations'] }}</p>
            <p class="pt-1 text-sm text-slate-500">Chats sin usuario vinculado.</p>
        </x-ui.card>

        <x-ui.card size="full">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Webhook Meta</p>
            <div class="pt-2 flex items-center gap-2">
                <x-ui.badge :color="($webhookSettings?->webhook_last_status ?? null) === 'ok' ? 'emerald' : (($webhookSettings?->webhook_last_status ?? null) === 'invalid_signature' ? 'rose' : 'blue')" size="sm" pill>
                    {{ strtoupper($webhookSettings?->webhook_last_status ?? 'sin_estado') }}
                </x-ui.badge>
            </div>
            <p class="pt-2 text-sm text-slate-500">
                {{ $webhookSettings?->webhook_last_received_at?->format('d/m/Y H:i:s') ?? 'Sin eventos todavía' }}
            </p>
        </x-ui.card>
    </div>

    <x-ui.card size="full">
        <div class="border-b border-slate-200 pb-4">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <x-ui.heading level="h3" size="sm">
                        Conversaciones
                    </x-ui.heading>
                    <p class="mt-1 text-sm text-slate-500">
                        Consola de seguimiento y respuesta para mensajes sincronizados por webhook.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500">
                    <span>{{ $conversations->count() }} visibles</span>
                    <span class="hidden h-1 w-1 rounded-full bg-slate-300 xl:block"></span>
                    <span>
                        {{ $selectedConversation ? 'Conversación seleccionada' : 'Sin conversación seleccionada' }}
                    </span>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-end gap-3">
                <div class="min-w-[16rem] flex-[1.6_1_20rem]">
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
                        Buscar conversación
                    </label>
                    <input wire:model.live.debounce.300ms="search" type="text"
                        placeholder="Buscar por nombre, teléfono o asignado..."
                        class="w-full rounded-box border border-black/10 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm transition-colors focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-50 dark:focus:border-white/20 dark:focus:ring-neutral-100/15" />
                </div>

                <div class="w-full sm:w-44">
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
                        Estatus
                    </label>
                    <select wire:model.live="statusFilter"
                        class="w-full rounded-box border border-black/10 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm transition-colors focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-50 dark:focus:border-white/20 dark:focus:ring-neutral-100/15">
                        <option value="all">Todos</option>
                        <option value="open">Abiertos</option>
                        <option value="archived">Archivados</option>
                    </select>
                </div>

                <div class="w-full sm:w-48">
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
                        Tipo de contacto
                    </label>
                    <select wire:model.live="linkedFilter"
                        class="w-full rounded-box border border-black/10 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm transition-colors focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-50 dark:focus:border-white/20 dark:focus:ring-neutral-100/15">
                        <option value="all">Todos</option>
                        <option value="prospects">Prospectos</option>
                        <option value="users">Usuarios vinculados</option>
                    </select>
                </div>

                <div class="w-full sm:w-48">
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
                        Etiqueta
                    </label>
                    <select wire:model.live="filterTagId"
                        class="w-full rounded-box border border-black/10 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm transition-colors focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-50 dark:focus:border-white/20 dark:focus:ring-neutral-100/15">
                        <option value="">Todas</option>
                        @foreach ($availableTags as $tag)
                            <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="min-w-[16rem] flex-[1_1_18rem]">
                    <label
                        class="flex w-full items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        <span>
                            <span class="block font-medium text-slate-900">Solo no leídos</span>
                            <span class="block text-xs text-slate-500">Oculta conversaciones ya revisadas.</span>
                        </span>

                        <x-ui.switch
                            wire:key="unread-only-switch-{{ $unreadOnly ? '1' : '0' }}"
                            wire:model.live="unreadOnly"
                            :checked="$unreadOnly"
                            color="teal"
                        />
                    </label>
                </div>
            </div>
        </div>

        <div
            class="mt-4 grid min-h-[48rem] gap-0 overflow-hidden rounded-2xl border border-slate-200 xl:h-[42rem] xl:min-h-0 xl:grid-cols-[22rem_minmax(0,1fr)]">
            <aside
                class="flex min-h-[20rem] flex-col border-b border-slate-200 bg-slate-50/70 xl:min-h-0 xl:border-b-0 xl:border-r">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Conversaciones</p>
                        <p class="text-xs text-slate-500">Selecciona para ver el detalle.</p>
                    </div>

                    <div class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-500 shadow-sm">
                        {{ $conversations->count() }}
                    </div>
                </div>

                <div class="flex-1 space-y-2 overflow-y-auto p-3">
                    @forelse ($conversations as $conversation)
                        @php
                            $contact = $conversation->contact;
                            $isSelected = $selectedConversationId === $conversation->id;
                            $lastMessage = $conversation->latestMessage;
                        @endphp

                        <div wire:key="conversation-list-item-{{ $conversation->id }}"
                            wire:click="selectConversation({{ $conversation->id }})"
                            wire:keydown.enter="selectConversation({{ $conversation->id }})"
                            wire:keydown.space.prevent="selectConversation({{ $conversation->id }})" role="button"
                            tabindex="0"
                            class="{{ $isSelected ? 'border-teal-300 bg-white shadow-sm ring-2 ring-teal-100' : 'border-transparent bg-white/80 hover:border-slate-200 hover:bg-white hover:shadow-sm' }} cursor-pointer rounded-2xl border px-4 py-3 text-left transition focus:outline-none focus:ring-2 focus:ring-teal-200">
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
                                        <span class="text-[11px] text-slate-400">
                                            {{ $conversation->last_message_at->format('d/m H:i') }}
                                        </span>
                                    @endif

                                    @if ($contact->unread_count > 0)
                                        <span
                                            class="inline-flex min-w-6 items-center justify-center rounded-full bg-teal-500 px-2 py-1 text-[11px] font-semibold text-white">
                                            {{ $contact->unread_count }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-2 text-[11px]">
                                <span
                                    class="rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 font-medium text-slate-600">
                                    {{ $contact->user?->profile ?? 'Prospecto' }}
                                </span>

                                @if ($conversation->assignedUser)
                                    <span
                                        class="rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 font-medium text-blue-700">
                                        {{ $conversation->assignedUser->name }}
                                    </span>
                                @endif

                                @if ($conversation->status === 'archived')
                                    <span
                                        class="rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 font-medium text-rose-700">
                                        Archivado
                                    </span>
                                @endif
                            </div>

                            <p class="mt-3 truncate text-xs leading-5 text-slate-500">
                                {{ $lastMessage?->body_text ?? 'Sin mensajes aún' }}
                            </p>
                        </div>
                    @empty
                        <div
                            class="flex h-full min-h-[16rem] items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">
                            No hay conversaciones registradas con los filtros actuales.
                        </div>
                    @endforelse
                </div>
            </aside>

            <section class="flex min-w-0 overflow-hidden bg-white">
                @if ($selectedConversation)
                    @include('livewire.whatsapp.conversation-detail-panel')
                @else
                    <div
                        class="flex h-full min-h-[24rem] w-full items-center justify-center bg-[radial-gradient(circle_at_center,_rgba(20,184,166,0.10),_transparent_22rem)] px-6 py-10 xl:min-h-0">
                        <div class="text-center">
                            <div
                                class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-teal-50 text-teal-600 shadow-inner">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="h-12 w-12">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 12.76c0 1.6.88 3.07 2.299 3.825.169.09.282.266.282.457v2.007a.75.75 0 0 0 1.28.53l1.498-1.498a.75.75 0 0 1 .53-.22h5.962c1.6 0 3.07-.88 3.825-2.299M2.25 12.76V8.25A2.25 2.25 0 0 1 4.5 6h15A2.25 2.25 0 0 1 21.75 8.25v4.51A2.25 2.25 0 0 1 19.5 15h-1.5" />
                                </svg>
                            </div>

                            <p class="mt-6 text-lg font-semibold text-slate-700">
                                Selecciona una conversación
                            </p>
                            <p class="mt-2 text-sm text-slate-500">
                                Selecciona una conversación para ver el detalle.
                            </p>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </x-ui.card>

    <x-ui.card size="full">
        <div class="flex items-center justify-between gap-3">
            <div>
                <x-ui.heading level="h3" size="sm">
                    Eventos Webhook
                </x-ui.heading>
                <p class="mt-1 text-sm text-slate-500">
                    Últimos eventos recibidos desde Meta.
                </p>
            </div>

            <div class="text-xs text-slate-500">
                {{ $webhookEvents->count() }} recientes
            </div>
        </div>

        <div class="mt-4 grid gap-3 xl:grid-cols-4">
            @forelse ($webhookEvents as $event)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.badge variant="outline" size="sm" pill>
                            {{ $event->event_type ?? 'unknown' }}
                        </x-ui.badge>

                        <x-ui.badge :color="$event->signature_valid ? 'emerald' : 'rose'" size="sm" pill>
                            {{ $event->signature_valid ? 'firma_ok' : 'firma_invalida' }}
                        </x-ui.badge>

                        @if ($event->processed_at)
                            <x-ui.badge color="blue" variant="outline" size="sm" pill>
                                procesado
                            </x-ui.badge>
                        @endif
                    </div>

                    <p class="mt-3 text-xs text-slate-500">
                        {{ $event->created_at?->format('d/m/Y H:i:s') ?? 'Sin fecha' }}
                    </p>

                    <p class="mt-1 font-mono text-[11px] text-slate-400">
                        {{ substr($event->event_hash, 0, 16) }}...
                    </p>
                </div>
            @empty
                <div
                    class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 xl:col-span-4">
                    Aún no hay eventos webhook persistidos.
                </div>
            @endforelse
        </div>
    </x-ui.card>
</div>

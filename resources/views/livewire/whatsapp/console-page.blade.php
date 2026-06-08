<div>
    <x-slot name="header">
        {{ __('app.whatsapp_console') }}
    </x-slot>

    <div class="grid gap-4 lg:grid-cols-[24rem_minmax(0,1fr)]">
        <x-ui.card size="full">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <x-ui.heading level="h3" size="sm">
                        Conversaciones
                    </x-ui.heading>
                    <p class="mt-1 text-sm text-slate-500">
                        Entrantes y salientes sincronizados por webhook.
                    </p>
                </div>

                <div class="text-right text-xs text-slate-500">
                    {{ $conversations->count() }} visibles
                </div>
            </div>

            <div class="grid gap-3 pt-4">
                <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o teléfono..." />

                <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                    <div>
                        <p class="text-sm font-medium text-slate-900">Solo no leídos</p>
                        <p class="text-xs text-slate-500">Muestra solo chats con mensajes pendientes.</p>
                    </div>

                    <x-ui.switch wire:model.live="unreadOnly" :checked="$unreadOnly" color="teal" />
                </div>
            </div>

            <div class="pt-4">
                <div class="space-y-2">
                    @forelse ($conversations as $conversation)
                        @php
                            $contact = $conversation->contact;
                            $isSelected = $selectedConversation?->id === $conversation->id;
                            $lastMessage = $conversation->latestMessage;
                        @endphp

                        <button
                            type="button"
                            wire:click="selectConversation({{ $conversation->id }})"
                            class="{{ $isSelected ? 'border-teal-300 bg-teal-50' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50' }} w-full rounded-xl border p-3 text-left transition"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">
                                        {{ $contact->user?->name ?? $contact->name ?? 'Sin nombre' }}
                                    </p>
                                    <p class="truncate text-xs text-slate-500">
                                        {{ $contact->phone ?? $contact->normalized_phone }}
                                    </p>
                                </div>

                                <div class="flex flex-col items-end gap-2">
                                    @if ($contact->unread_count > 0)
                                        <x-ui.badge color="teal" size="sm" pill>
                                            {{ $contact->unread_count }} nuevo{{ $contact->unread_count > 1 ? 's' : '' }}
                                        </x-ui.badge>
                                    @endif

                                    @if ($conversation->last_message_at)
                                        <span class="text-[11px] text-slate-400">
                                            {{ $conversation->last_message_at->format('d/m H:i') }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <p class="pt-2 text-xs text-slate-600">
                                {{ $lastMessage?->body_text ?? 'Sin mensajes aún' }}
                            </p>
                        </button>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                            No hay conversaciones registradas todavía.
                        </div>
                    @endforelse
                </div>
            </div>
        </x-ui.card>

        <x-ui.card size="full">
            @if ($selectedConversation)
                @php
                    $contact = $selectedConversation->contact;
                @endphp

                <div class="flex items-center justify-between gap-3 border-b border-slate-200 pb-4">
                    <div>
                        <x-ui.heading level="h3" size="sm">
                            {{ $contact->user?->name ?? $contact->name ?? 'Sin nombre' }}
                        </x-ui.heading>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $contact->phone ?? $contact->normalized_phone }}
                        </p>
                        @if ($contact->user)
                            <p class="mt-1 text-xs text-slate-400">
                                Vinculado al usuario #{{ $contact->user->id }} ({{ $contact->user->profile }})
                            </p>
                        @else
                            <p class="mt-1 text-xs text-slate-400">
                                Prospecto sin usuario vinculado.
                            </p>
                        @endif
                    </div>

                    <div class="text-right text-xs text-slate-500">
                        @if ($selectedConversation->last_message_at)
                            Último movimiento: {{ $selectedConversation->last_message_at->format('d/m/Y H:i') }}
                        @endif
                    </div>
                </div>

                <div class="space-y-3 pt-4">
                    @forelse ($selectedMessages as $message)
                        @php
                            $isOutbound = $message->direction === \App\Models\WhatsAppMessage::DIRECTION_OUTBOUND;
                            $alignment = $isOutbound ? 'justify-end' : 'justify-start';
                            $bubbleClasses = $isOutbound
                                ? 'bg-teal-600 text-white border-teal-500'
                                : 'bg-slate-50 text-slate-900 border-slate-200';
                        @endphp

                        <div class="flex {{ $alignment }}">
                            <div class="{{ $bubbleClasses }} max-w-2xl rounded-2xl border px-4 py-3 shadow-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <span class="text-[11px] font-semibold uppercase tracking-wide {{ $isOutbound ? 'text-teal-100' : 'text-slate-500' }}">
                                        {{ $isOutbound ? 'Enviado' : 'Recibido' }}
                                    </span>

                                    <span class="text-[11px] {{ $isOutbound ? 'text-teal-100' : 'text-slate-400' }}">
                                        {{ $message->created_at?->format('d/m/Y H:i') }}
                                    </span>
                                </div>

                                <div class="pt-2 text-sm leading-6">
                                    {{ $message->body_text ?: '[Sin vista previa]' }}
                                </div>

                                <div class="flex flex-wrap items-center gap-2 pt-3">
                                    <x-ui.badge :color="$message->status === 'failed' ? 'rose' : ($message->status === 'read' ? 'emerald' : ($message->status === 'delivered' ? 'blue' : 'teal'))" size="sm" pill>
                                        {{ strtoupper($message->status) }}
                                    </x-ui.badge>

                                    @if ($message->template_name)
                                        <x-ui.badge variant="outline" size="sm" pill>
                                            {{ $message->template_name }}
                                        </x-ui.badge>
                                    @endif

                                    @if ($message->meta_message_id)
                                        <span class="text-[11px] {{ $isOutbound ? 'text-teal-100' : 'text-slate-400' }}">
                                            ID: {{ $message->meta_message_id }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                            Esta conversación aún no tiene mensajes persistidos.
                        </div>
                    @endforelse
                </div>
            @else
                <div class="flex min-h-[28rem] items-center justify-center rounded-xl border border-dashed border-slate-300 text-sm text-slate-500">
                    Selecciona una conversación para ver el detalle.
                </div>
            @endif
        </x-ui.card>
    </div>
</div>

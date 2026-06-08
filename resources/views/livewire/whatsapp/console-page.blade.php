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

    <div class="grid gap-4 xl:grid-cols-[24rem_minmax(0,1fr)]">
        <div class="space-y-4">
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
                    <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre, teléfono o asignado..." />

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-1">
                        <div>
                            <x-ui.label>Estatus</x-ui.label>
                            <x-ui.select wire:model.live="statusFilter">
                                <x-ui.select.option value="all">Todos</x-ui.select.option>
                                <x-ui.select.option value="open">Abiertos</x-ui.select.option>
                                <x-ui.select.option value="archived">Archivados</x-ui.select.option>
                            </x-ui.select>
                        </div>

                        <div>
                            <x-ui.label>Tipo de contacto</x-ui.label>
                            <x-ui.select wire:model.live="linkedFilter">
                                <x-ui.select.option value="all">Todos</x-ui.select.option>
                                <x-ui.select.option value="prospects">Prospectos</x-ui.select.option>
                                <x-ui.select.option value="users">Usuarios vinculados</x-ui.select.option>
                            </x-ui.select>
                        </div>

                        <div class="md:col-span-2 xl:col-span-1">
                            <x-ui.label>Etiqueta</x-ui.label>
                            <x-ui.select wire:model.live="filterTagId">
                                <x-ui.select.option value="">Todas</x-ui.select.option>
                                @foreach ($availableTags as $tag)
                                    <x-ui.select.option value="{{ $tag->id }}">
                                        {{ $tag->name }}
                                    </x-ui.select.option>
                                @endforeach
                            </x-ui.select>
                        </div>
                    </div>

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
                                wire:key="conversation-list-item-{{ $conversation->id }}"
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

                                <div class="flex flex-wrap items-center gap-2 pt-2">
                                    @if ($contact->user)
                                        <x-ui.badge variant="outline" size="sm" pill>
                                            {{ $contact->user->profile }}
                                        </x-ui.badge>
                                    @else
                                        <x-ui.badge color="amber" size="sm" pill>
                                            Prospecto
                                        </x-ui.badge>
                                    @endif

                                    @if ($conversation->assignedUser)
                                        <x-ui.badge color="blue" variant="outline" size="sm" pill>
                                            {{ $conversation->assignedUser->name }}
                                        </x-ui.badge>
                                    @endif

                                    @if ($conversation->status === 'archived')
                                        <x-ui.badge color="rose" variant="outline" size="sm" pill>
                                            Archivado
                                        </x-ui.badge>
                                    @endif

                                    @foreach ($conversation->tags as $tag)
                                        <x-ui.badge :color="$tag->color" variant="outline" size="sm" pill>
                                            {{ $tag->name }}
                                        </x-ui.badge>
                                    @endforeach
                                </div>

                                <p class="truncate pt-2 text-xs text-slate-600">
                                    {{ $lastMessage?->body_text ?? 'Sin mensajes aún' }}
                                </p>
                            </button>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                                No hay conversaciones registradas con los filtros actuales.
                            </div>
                        @endforelse
                    </div>
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

                <div class="space-y-2 pt-4">
                    @forelse ($webhookEvents as $event)
                        <div class="rounded-xl border border-slate-200 p-3">
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

                            <p class="pt-2 text-xs text-slate-500">
                                {{ $event->created_at?->format('d/m/Y H:i:s') ?? 'Sin fecha' }}
                            </p>

                            <p class="pt-1 font-mono text-[11px] text-slate-400">
                                {{ substr($event->event_hash, 0, 16) }}...
                            </p>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                            Aún no hay eventos webhook persistidos.
                        </div>
                    @endforelse
                </div>
            </x-ui.card>
        </div>

        <x-ui.card
            size="full"
            wire:key="conversation-detail-{{ $selectedConversation?->id ?? 'empty' }}"
        >
            @if ($selectedConversation)
                @php
                    $contact = $selectedConversation->contact;
                @endphp

                <div class="flex flex-col gap-4 border-b border-slate-200 pb-4 xl:flex-row xl:items-start xl:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-ui.heading level="h3" size="sm">
                                {{ $contact->user?->name ?? $contact->name ?? 'Sin nombre' }}
                            </x-ui.heading>

                            <x-ui.badge :color="$selectedConversation->status === 'archived' ? 'rose' : 'emerald'" size="sm" pill>
                                {{ $selectedConversation->status === 'archived' ? 'Archivado' : 'Abierto' }}
                            </x-ui.badge>

                            @if (! $contact->user)
                                <x-ui.badge color="amber" variant="outline" size="sm" pill>
                                    Prospecto
                                </x-ui.badge>
                            @endif
                        </div>

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

                        @if ($selectedConversation->archived_at)
                            <p class="mt-1 text-xs text-slate-400">
                                Archivado el {{ $selectedConversation->archived_at->format('d/m/Y H:i') }}
                            </p>
                        @endif
                    </div>

                    <div class="grid gap-3 md:grid-cols-[16rem_auto_auto_auto]">
                        <div>
                            <x-ui.label>Asignado a</x-ui.label>
                            <x-ui.select wire:model.live="assignedUserId" placeholder="Sin asignar">
                                <x-ui.select.option value="">Sin asignar</x-ui.select.option>
                                @foreach ($assignableUsers as $user)
                                    <x-ui.select.option value="{{ $user->id }}">
                                        {{ $user->name }} ({{ $user->profile }})
                                    </x-ui.select.option>
                                @endforeach
                            </x-ui.select>
                        </div>

                        <div class="flex items-end">
                            <x-ui.button
                                type="button"
                                icon="check-circle"
                                variant="outline"
                                color="teal"
                                wire:click="markSelectedConversationAsRead"
                            >
                                Marcar leído
                            </x-ui.button>
                        </div>

                        @if ($selectedConversation->status === 'archived')
                            <div class="flex items-end">
                                <x-ui.button
                                    type="button"
                                    icon="arrow-path"
                                    variant="outline"
                                    color="blue"
                                    wire:click="reopenSelectedConversation"
                                >
                                    Reabrir
                                </x-ui.button>
                            </div>
                        @else
                            <div class="flex items-end">
                                <x-ui.button
                                    type="button"
                                    icon="archive-box"
                                    variant="outline"
                                    color="rose"
                                    wire:click="archiveSelectedConversation"
                                >
                                    Archivar
                                </x-ui.button>
                            </div>
                        @endif

                        <div class="flex items-end text-right text-xs text-slate-500">
                            @if ($selectedConversation->last_message_at)
                                Último movimiento: {{ $selectedConversation->last_message_at->format('d/m/Y H:i') }}
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 border-b border-slate-200 py-4 xl:grid-cols-[minmax(0,1fr)_22rem]">
                    <div>
                        <x-ui.label>Etiquetas asignadas</x-ui.label>

                        <div class="flex flex-wrap gap-2 pt-2">
                            @forelse ($selectedConversation->tags as $tag)
                                <div class="flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2 py-1">
                                    <x-ui.badge :color="$tag->color" variant="outline" size="sm" pill>
                                        {{ $tag->name }}
                                    </x-ui.badge>

                                    <x-ui.button
                                        type="button"
                                        icon="x-mark"
                                        size="sm"
                                        variant="ghost"
                                        color="rose"
                                        wire:click="detachTag({{ $tag->id }})"
                                    />
                                </div>
                            @empty
                                <p class="text-sm text-slate-500">
                                    Esta conversación aún no tiene etiquetas.
                                </p>
                            @endforelse
                        </div>
                    </div>

                    <div class="grid gap-3">
                        <div>
                            <x-ui.label>Asignar etiqueta existente</x-ui.label>
                            <div class="flex gap-2">
                                <x-ui.select wire:model.live="selectedTagId" placeholder="Selecciona una etiqueta">
                                    <x-ui.select.option value="">Selecciona una etiqueta</x-ui.select.option>
                                    @foreach ($availableTags as $tag)
                                        <x-ui.select.option value="{{ $tag->id }}">
                                            {{ $tag->name }}
                                        </x-ui.select.option>
                                    @endforeach
                                </x-ui.select>

                                <x-ui.button
                                    type="button"
                                    icon="plus-circle"
                                    variant="outline"
                                    color="teal"
                                    wire:click="attachSelectedTag"
                                >
                                    Agregar
                                </x-ui.button>
                            </div>
                            <x-ui.error name="selectedTagId" />
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-sm font-medium text-slate-900">Nueva etiqueta</p>

                            <div class="grid gap-3 pt-3">
                                <div>
                                    <x-ui.label>Nombre</x-ui.label>
                                    <x-ui.input wire:model.live="newTagName" placeholder="Ej. Urgente, Seguimiento, Cobranza" />
                                    <x-ui.error name="newTagName" />
                                </div>

                                <div>
                                    <x-ui.label>Color</x-ui.label>
                                    <x-ui.select wire:model.live="newTagColor">
                                        @foreach ($tagColors as $color)
                                            <x-ui.select.option value="{{ $color }}">
                                                {{ ucfirst($color) }}
                                            </x-ui.select.option>
                                        @endforeach
                                    </x-ui.select>
                                    <x-ui.error name="newTagColor" />
                                </div>

                                <div class="flex justify-end">
                                    <x-ui.button
                                        type="button"
                                        icon="plus-circle"
                                        variant="primary"
                                        color="teal"
                                        wire:click="createTag"
                                    >
                                        Crear etiqueta
                                    </x-ui.button>
                                </div>
                            </div>
                        </div>
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

                        <div
                            class="flex {{ $alignment }}"
                            wire:key="conversation-message-{{ $message->id }}"
                        >
                            <div class="{{ $bubbleClasses }} max-w-2xl rounded-2xl border px-4 py-3 shadow-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <span class="text-[11px] font-semibold uppercase tracking-wide {{ $isOutbound ? 'text-teal-100' : 'text-slate-500' }}">
                                        {{ $isOutbound ? 'Enviado' : 'Recibido' }}
                                    </span>

                                    <span class="text-[11px] {{ $isOutbound ? 'text-teal-100' : 'text-slate-400' }}">
                                        {{ ($message->received_at ?? $message->created_at)?->format('d/m/Y H:i') }}
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

                                    @if ($message->meta_pricing_category)
                                        <x-ui.badge color="blue" variant="outline" size="sm" pill>
                                            {{ $message->meta_pricing_category }}
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

                <form wire:submit="sendReply" class="mt-4 border-t border-slate-200 pt-4">
                    <div class="grid gap-3">
                        <div>
                            <x-ui.label>Responder por WhatsApp</x-ui.label>
                            <x-ui.textarea
                                wire:model.live="replyMessage"
                                rows="4"
                                placeholder="Escribe aquí la respuesta para el cliente..."
                            />
                            <p class="mt-1 text-xs text-slate-500">
                                Meta solo permite texto libre dentro de la ventana activa de conversación.
                            </p>
                            <x-ui.error name="replyMessage" />
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs text-slate-400">
                                El mensaje se guardará también en esta consola.
                            </p>

                            <x-ui.button
                                type="submit"
                                icon="paper-airplane"
                                variant="primary"
                                color="teal"
                            >
                                Enviar mensaje
                            </x-ui.button>
                        </div>
                    </div>
                </form>
            @else
                <div class="flex min-h-[28rem] items-center justify-center rounded-xl border border-dashed border-slate-300 text-sm text-slate-500">
                    Selecciona una conversación para ver el detalle.
                </div>
            @endif
        </x-ui.card>
    </div>
</div>

<x-mcp::app :title="$title">
    <x-slot:head>
        <script type="module">
        createMcpApp(async (app) => {
            const root = document.getElementById('app');

            window.refreshQueue = async () => {
                const result = await app.callServerTool('get_queue_data', {});

                if (result.isError) {
                    root.dispatchEvent(new CustomEvent('queue-error', {
                        detail: result.content[0]?.text ?? 'Could not load the queue.',
                    }));

                    return;
                }

                const payload = JSON.parse(result.content[0]?.text ?? '{}');
                root.dispatchEvent(new CustomEvent('queue-loaded', {
                    detail: payload.tickets ?? [],
                }));
            };

            root.dispatchEvent(new CustomEvent('mcp-ready'));
        });
        </script>
    </x-slot:head>

    <div
        id="app"
        class="min-h-screen p-6 text-sm"
        x-data="{ ready: false, loading: false, error: '', tickets: [] }"
        @mcp-ready="ready = true"
        @queue-error="error = $event.detail; loading = false"
        @queue-loaded="tickets = $event.detail; error = ''; loading = false"
    >
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-lg font-semibold">Open queue</h1>
            <button
                id="run-btn"
                type="button"
                class="rounded border px-3 py-1 disabled:opacity-50"
                :disabled="!ready || loading"
                @click="loading = true; error = ''; window.refreshQueue()"
            >
                Refresh
            </button>
        </div>

        <p class="text-red-600" x-show="error" x-text="error"></p>
        <p x-show="ready && !loading && !error && tickets.length === 0">No open tickets.</p>

        <ul class="space-y-2">
            <template x-for="ticket in tickets" :key="ticket.id">
                <li class="rounded border p-3">
                    <div class="font-medium" x-text="'#' + ticket.id + ' ' + ticket.subject"></div>
                    <div class="text-xs opacity-70" x-text="ticket.priority + ' · ' + ticket.requester + ' → ' + (ticket.assignee ?? 'Unassigned')"></div>
                </li>
            </template>
        </ul>
    </div>
</x-mcp::app>

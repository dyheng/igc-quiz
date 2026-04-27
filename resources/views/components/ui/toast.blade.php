<div
    x-data="{ items: [] }"
    x-on:toast.window="items.push({ id: Date.now() + Math.random(), text: $event.detail.message, variant: $event.detail.variant ?? 'success' }); setTimeout(() => items.shift(), 2400);"
    class="fixed top-4 right-4 z-50 space-y-2 pointer-events-none"
    style="padding-top: env(safe-area-inset-top);"
>
    <template x-for="t in items" :key="t.id">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="card p-3 px-4 shadow-card pointer-events-auto"
            :class="{
                'border-emerald-200 bg-emerald-50 text-emerald-900': t.variant === 'success',
                'border-rose-200 bg-rose-50 text-rose-900': t.variant === 'danger',
                'border-slate-200 bg-white text-slate-900': t.variant === 'info',
            }"
        >
            <p class="text-sm font-medium" x-text="t.text"></p>
        </div>
    </template>
</div>

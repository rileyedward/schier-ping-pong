<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

type PlayerLike = {
    id: number;
    first_name?: string;
    last_name?: string;
    name?: string;
    league_rating?: number;
};

const props = withDefaults(
    defineProps<{
        modelValue: number;
        players: PlayerLike[];
        label?: string;
        placeholder?: string;
        exclude?: number[];
        disabled?: boolean;
    }>(),
    {
        label: 'Select player',
        placeholder: 'Tap to pick a player',
        exclude: () => [],
        disabled: false,
    },
);

const emit = defineEmits<{ (e: 'update:modelValue', v: number): void }>();

const open = ref(false);
const search = ref('');

watch(open, (v) => {
    if (v) search.value = '';
});

const palette = ['#3f9c6b', '#3b8ab0', '#2a6b82', '#c97a3a', '#8b5cf6', '#e11d48'];
function colorFor(id: number) {
    return palette[id % palette.length];
}

function displayName(p: PlayerLike) {
    if (p.name) return p.name;
    return [p.first_name, p.last_name].filter(Boolean).join(' ');
}

function initials(p: PlayerLike) {
    return displayName(p)
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((s) => s[0]?.toUpperCase() ?? '')
        .join('');
}

const selected = computed(() => props.players.find((p) => p.id === props.modelValue) ?? null);

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.players
        .filter((p) => !props.exclude.includes(p.id))
        .filter((p) => !q || displayName(p).toLowerCase().includes(q));
});

function pick(p: PlayerLike) {
    emit('update:modelValue', p.id);
    open.value = false;
}

function clear(e: Event) {
    e.stopPropagation();
    emit('update:modelValue', 0);
}
</script>

<template>
    <div>
        <button
            type="button"
            :disabled="disabled"
            class="flex min-h-12 w-full items-center gap-3 rounded-md border bg-white px-3 py-2 text-left text-sm shadow-sm transition-colors hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50"
            @click="open = true"
        >
            <template v-if="selected">
                <!-- TODO: replace placeholder circle with player profile image -->
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white"
                    :style="{ backgroundColor: colorFor(selected.id) }"
                >
                    {{ initials(selected) }}
                </div>
                <span class="flex-1 truncate font-medium text-neutral-900">{{ displayName(selected) }}</span>
                <button
                    type="button"
                    class="text-xs text-neutral-400 hover:text-neutral-700"
                    aria-label="Clear"
                    @click="clear"
                >
                    ✕
                </button>
            </template>
            <template v-else>
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-neutral-400">
                    +
                </div>
                <span class="flex-1 text-neutral-400">{{ placeholder }}</span>
            </template>
        </button>

        <Dialog v-model:open="open">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ label }}</DialogTitle>
                </DialogHeader>

                <Input
                    v-model="search"
                    placeholder="Search players…"
                    class="h-11 text-base"
                    autofocus
                />

                <div class="max-h-[60vh] overflow-y-auto pr-1">
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <button
                            v-for="p in filtered"
                            :key="p.id"
                            type="button"
                            class="flex items-center gap-3 rounded-lg border bg-white p-3 text-left transition-colors hover:border-neutral-400 active:bg-neutral-50"
                            @click="pick(p)"
                        >
                            <!-- TODO: replace placeholder circle with player profile image -->
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-sm font-semibold text-white"
                                :style="{ backgroundColor: colorFor(p.id) }"
                            >
                                {{ initials(p) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-neutral-900">{{ displayName(p) }}</div>
                                <div v-if="p.league_rating !== undefined" class="text-xs text-neutral-500">
                                    Rating {{ p.league_rating }}
                                </div>
                            </div>
                        </button>
                    </div>
                    <div v-if="!filtered.length" class="py-12 text-center text-sm text-neutral-400">
                        No players match.
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>

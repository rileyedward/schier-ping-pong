<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type League = { id: number; name: string };
type PlayerRow = {
    id: number;
    name: string;
    first_name: string;
    last_name: string;
    league_rating: number;
    friendly_rating: number;
    leagues: League[];
    league_wins: number;
    league_losses: number;
    friendly_wins: number;
    friendly_losses: number;
    total_wins: number;
    total_losses: number;
};

const props = defineProps<{ players: PlayerRow[] }>();

const search = ref('');
const leagueFilter = ref<number | ''>('');
type SortKey = 'name' | 'league_rating' | 'friendly_rating' | 'total_wins';
const sortKey = ref<SortKey>('league_rating');
const sortDir = ref<'asc' | 'desc'>('desc');

const allLeagues = computed(() => {
    const seen = new Map<number, string>();

    for (const p of props.players) {
        for (const l of p.leagues) {
seen.set(l.id, l.name);
}
    }

    return [...seen.entries()].map(([id, name]) => ({ id, name })).sort((a, b) => a.name.localeCompare(b.name));
});

const filtered = computed(() => {
    let list = props.players;

    if (search.value.trim()) {
        const q = search.value.trim().toLowerCase();
        list = list.filter((p) => p.name.toLowerCase().includes(q));
    }

    if (leagueFilter.value !== '') {
        list = list.filter((p) => p.leagues.some((l) => l.id === leagueFilter.value));
    }

    return [...list].sort((a, b) => {
        const mul = sortDir.value === 'asc' ? 1 : -1;

        if (sortKey.value === 'name') {
return mul * a.name.localeCompare(b.name);
}

        return mul * (a[sortKey.value] - b[sortKey.value]);
    });
});

function setSort(key: SortKey) {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value = key === 'name' ? 'asc' : 'desc';
    }
}

function sortIcon(key: SortKey) {
    if (sortKey.value !== key) {
return '↕';
}

    return sortDir.value === 'asc' ? '↑' : '↓';
}
</script>

<template>
    <Head title="Players" />
    <div class="min-h-screen text-neutral-900" style="background-color: #f4f1ea">
        <header class="bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-5">
                <div class="flex items-center gap-3">
                    <Link href="/" class="text-sm text-neutral-500 hover:underline">← Home</Link>
                    <!-- TODO: replace placeholder with Schier logo asset -->
                    <div class="flex h-10 w-10 items-center justify-center rounded-full" style="background-color: #3f9c6b">
                        <div class="h-5 w-5 rounded-full bg-white"></div>
                    </div>
                    <h1 class="text-xl font-semibold tracking-wide">ALL PLAYERS</h1>
                </div>
                <span class="text-sm text-neutral-500">{{ players.length }} players</span>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-6 py-8">
            <Card class="border-0 shadow-sm">
                <CardHeader style="background-color: #3f9c6b" class="rounded-t-xl text-white">
                    <CardTitle class="tracking-wide">Players</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4 pt-4">
                    <!-- Filters -->
                    <div class="flex flex-wrap gap-3">
                        <Input
                            v-model="search"
                            placeholder="Search by name…"
                            class="w-56"
                        />
                        <select
                            v-model="leagueFilter"
                            class="h-9 rounded-md border bg-transparent px-2 text-sm"
                        >
                            <option value="">All leagues</option>
                            <option v-for="l in allLeagues" :key="l.id" :value="l.id">{{ l.name }}</option>
                        </select>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto rounded-md">
                        <table class="w-full text-sm">
                            <thead class="text-left text-xs text-white" style="background-color: #3f9c6b">
                                <tr>
                                    <th class="px-3 py-2">
                                        <button class="flex items-center gap-1 hover:opacity-80" @click="setSort('name')">
                                            Name <span>{{ sortIcon('name') }}</span>
                                        </button>
                                    </th>
                                    <th class="px-3 py-2">Leagues</th>
                                    <th class="px-3 py-2 text-right">
                                        <button class="flex items-center gap-1 ml-auto hover:opacity-80" @click="setSort('league_rating')">
                                            League Rtg <span>{{ sortIcon('league_rating') }}</span>
                                        </button>
                                    </th>
                                    <th class="px-3 py-2 text-right">
                                        <button class="flex items-center gap-1 ml-auto hover:opacity-80" @click="setSort('friendly_rating')">
                                            Friendly Rtg <span>{{ sortIcon('friendly_rating') }}</span>
                                        </button>
                                    </th>
                                    <th class="px-3 py-2 text-right">
                                        <button class="flex items-center gap-1 ml-auto hover:opacity-80" @click="setSort('total_wins')">
                                            W / L <span>{{ sortIcon('total_wins') }}</span>
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(p, i) in filtered"
                                    :key="p.id"
                                    :class="i % 2 === 0 ? 'bg-white' : ''"
                                    :style="i % 2 === 1 ? { backgroundColor: '#c8e6d0' } : {}"
                                >
                                    <td class="px-3 py-2">
                                        <Link
                                            :href="`/players/${p.id}`"
                                            class="font-medium hover:underline"
                                        >{{ p.name }}</Link>
                                    </td>
                                    <td class="px-3 py-2 text-xs text-neutral-600">
                                        {{ p.leagues.map((l) => l.name).join(', ') || '—' }}
                                    </td>
                                    <td class="px-3 py-2 text-right font-mono">{{ p.league_rating }}</td>
                                    <td class="px-3 py-2 text-right font-mono">{{ p.friendly_rating }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <span class="font-semibold text-emerald-700">{{ p.total_wins }}W</span>
                                        <span class="mx-1 text-neutral-400">/</span>
                                        <span class="text-red-600">{{ p.total_losses }}L</span>
                                    </td>
                                </tr>
                                <tr v-if="!filtered.length">
                                    <td colspan="5" class="py-6 text-center text-neutral-500">No players found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </main>
    </div>
</template>

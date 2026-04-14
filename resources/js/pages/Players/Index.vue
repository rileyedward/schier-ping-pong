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
        for (const l of p.leagues) seen.set(l.id, l.name);
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
        if (sortKey.value === 'name') return mul * a.name.localeCompare(b.name);
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
    if (sortKey.value !== key) return '↕';
    return sortDir.value === 'asc' ? '↑' : '↓';
}
</script>

<template>
    <Head title="Players" />
    <div class="min-h-screen bg-neutral-50 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">
        <header class="border-b bg-white dark:bg-neutral-900">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-3">
                    <Link href="/" class="text-sm text-neutral-500 hover:underline">← Home</Link>
                    <h1 class="text-xl font-semibold">All Players</h1>
                </div>
                <span class="text-sm text-neutral-500">{{ players.length }} players</span>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-6 py-8">
            <Card>
                <CardHeader>
                    <CardTitle>Players</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
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
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b text-left text-xs text-neutral-500">
                                <tr>
                                    <th class="py-2 pr-4">
                                        <button class="flex items-center gap-1 hover:text-neutral-800 dark:hover:text-neutral-200" @click="setSort('name')">
                                            Name <span>{{ sortIcon('name') }}</span>
                                        </button>
                                    </th>
                                    <th class="py-2 pr-4">Leagues</th>
                                    <th class="py-2 pr-4 text-right">
                                        <button class="flex items-center gap-1 ml-auto hover:text-neutral-800 dark:hover:text-neutral-200" @click="setSort('league_rating')">
                                            League Rtg <span>{{ sortIcon('league_rating') }}</span>
                                        </button>
                                    </th>
                                    <th class="py-2 pr-4 text-right">
                                        <button class="flex items-center gap-1 ml-auto hover:text-neutral-800 dark:hover:text-neutral-200" @click="setSort('friendly_rating')">
                                            Friendly Rtg <span>{{ sortIcon('friendly_rating') }}</span>
                                        </button>
                                    </th>
                                    <th class="py-2 text-right">
                                        <button class="flex items-center gap-1 ml-auto hover:text-neutral-800 dark:hover:text-neutral-200" @click="setSort('total_wins')">
                                            W / L <span>{{ sortIcon('total_wins') }}</span>
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="p in filtered"
                                    :key="p.id"
                                    class="hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                >
                                    <td class="py-2 pr-4">
                                        <Link
                                            :href="`/players/${p.id}`"
                                            class="font-medium hover:underline"
                                        >{{ p.name }}</Link>
                                    </td>
                                    <td class="py-2 pr-4 text-xs text-neutral-500">
                                        {{ p.leagues.map((l) => l.name).join(', ') || '—' }}
                                    </td>
                                    <td class="py-2 pr-4 text-right font-mono">{{ p.league_rating }}</td>
                                    <td class="py-2 pr-4 text-right font-mono">{{ p.friendly_rating }}</td>
                                    <td class="py-2 text-right">
                                        <span class="text-emerald-600 dark:text-emerald-400">{{ p.total_wins }}W</span>
                                        <span class="text-neutral-400 mx-1">/</span>
                                        <span class="text-red-500">{{ p.total_losses }}L</span>
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

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type League = { id: number; name: string };
type Player = {
    id: number;
    name: string;
    first_name: string;
    last_name: string;
    league_rating: number;
    friendly_rating: number;
    leagues: League[];
};
type Stats = {
    league_wins: number;
    league_losses: number;
    friendly_wins: number;
    friendly_losses: number;
    total_wins: number;
    total_losses: number;
};
type RatingPoint = { date: string; rating: number };

const props = defineProps<{
    player: Player;
    stats: Stats;
    leagueHistory: RatingPoint[];
    friendlyHistory: RatingPoint[];
}>();

type ChartLine = 'league' | 'friendly' | 'both';
const chartLine = ref<ChartLine>('both');

// --- SVG Chart logic ---
const W = 600;
const H = 200;
const PAD = { top: 16, right: 16, bottom: 32, left: 48 };

const allPoints = computed(() => {
    const pts: { date: string; rating: number }[] = [];

    if (chartLine.value !== 'friendly') {
pts.push(...props.leagueHistory);
}

    if (chartLine.value !== 'league') {
pts.push(...props.friendlyHistory);
}

    return pts;
});

const dateToX = computed(() => {
    const all = allPoints.value;

    if (!all.length) {
return () => PAD.left;
}

    const dates = all.map((p) => p.date).sort();
    const minD = dates[0];
    const maxD = dates[dates.length - 1];
    const span = minD === maxD ? 1 : new Date(maxD).getTime() - new Date(minD).getTime();

    return (date: string) => {
        const t = new Date(date).getTime() - new Date(minD).getTime();

        return PAD.left + (t / span) * (W - PAD.left - PAD.right);
    };
});

const ratingToY = computed(() => {
    const all = allPoints.value;

    if (!all.length) {
return () => H / 2;
}

    const ratings = all.map((p) => p.rating);
    const minR = Math.min(...ratings) - 30;
    const maxR = Math.max(...ratings) + 30;
    const span = maxR - minR || 1;

    return (r: number) => PAD.top + (1 - (r - minR) / span) * (H - PAD.top - PAD.bottom);
});

function toPolyline(points: RatingPoint[]) {
    if (!points.length) {
return '';
}

    return points
        .map((p) => `${dateToX.value(p.date).toFixed(1)},${ratingToY.value(p.rating).toFixed(1)}`)
        .join(' ');
}

const leagueLine = computed(() => toPolyline(props.leagueHistory));
const friendlyLine = computed(() => toPolyline(props.friendlyHistory));

// Y-axis tick labels
const yTicks = computed(() => {
    const all = allPoints.value;

    if (!all.length) {
return [1000];
}

    const ratings = all.map((p) => p.rating);
    const minR = Math.min(...ratings) - 30;
    const maxR = Math.max(...ratings) + 30;
    const step = Math.ceil((maxR - minR) / 4 / 10) * 10;
    const ticks: number[] = [];

    for (let v = Math.floor(minR / 10) * 10; v <= maxR; v += step) {
ticks.push(v);
}

    return ticks;
});

// X-axis tick labels (up to 5 dates)
const xTicks = computed(() => {
    const all = allPoints.value;

    if (!all.length) {
return [];
}

    const dates = [...new Set(all.map((p) => p.date))].sort();

    if (dates.length <= 5) {
return dates;
}

    const step = Math.floor(dates.length / 4);

    return [dates[0], dates[step], dates[step * 2], dates[step * 3], dates[dates.length - 1]];
});

const hasData = computed(() => allPoints.value.length > 0);
const initials = (name: string) =>
    name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((s) => s[0]?.toUpperCase() ?? '')
        .join('');
</script>

<template>
    <Head :title="player.name" />
    <div class="min-h-screen text-neutral-900" style="background-color: #f4f1ea">
        <header class="bg-white">
            <div class="mx-auto flex max-w-4xl items-center justify-between px-6 py-5">
                <div class="flex items-center gap-3">
                    <Link href="/players" class="text-sm text-neutral-500 hover:underline">← Players</Link>
                    <h1 class="text-xl font-semibold tracking-wide">{{ player.name }}</h1>
                </div>
                <!-- TODO: replace placeholder with player profile image -->
                <div class="flex h-12 w-12 items-center justify-center rounded-full text-sm font-semibold text-white" style="background-color: #3b8ab0">
                    {{ initials(player.name) }}
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-4xl space-y-6 px-6 py-8">
            <!-- Info + Ratings row -->
            <div class="grid gap-4 sm:grid-cols-3">
                <!-- Player info -->
                <Card>
                    <CardHeader><CardTitle class="text-base">Profile</CardTitle></CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <div>
                            <span class="text-neutral-500">Leagues</span>
                            <div class="mt-1 flex flex-wrap gap-1">
                                <span
                                    v-for="l in player.leagues"
                                    :key="l.id"
                                    class="rounded-full bg-neutral-100 px-2 py-0.5 text-xs dark:bg-neutral-800"
                                >{{ l.name }}</span>
                                <span v-if="!player.leagues.length" class="text-neutral-400">None</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- League rating -->
                <Card>
                    <CardHeader><CardTitle class="text-base">League Rating</CardTitle></CardHeader>
                    <CardContent>
                        <div class="text-4xl font-bold tabular-nums">{{ player.league_rating }}</div>
                        <div class="mt-2 text-sm text-neutral-500">
                            <span class="text-emerald-600 dark:text-emerald-400">{{ stats.league_wins }}W</span>
                            <span class="mx-1 text-neutral-400">/</span>
                            <span class="text-red-500">{{ stats.league_losses }}L</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Friendly rating -->
                <Card>
                    <CardHeader><CardTitle class="text-base">Friendly Rating</CardTitle></CardHeader>
                    <CardContent>
                        <div class="text-4xl font-bold tabular-nums">{{ player.friendly_rating }}</div>
                        <div class="mt-2 text-sm text-neutral-500">
                            <span class="text-emerald-600 dark:text-emerald-400">{{ stats.friendly_wins }}W</span>
                            <span class="mx-1 text-neutral-400">/</span>
                            <span class="text-red-500">{{ stats.friendly_losses }}L</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Overall record -->
            <Card>
                <CardHeader><CardTitle class="text-base">Overall Record</CardTitle></CardHeader>
                <CardContent>
                    <div class="flex flex-wrap gap-8 text-sm">
                        <div>
                            <div class="text-xs text-neutral-500 uppercase tracking-wide">Total</div>
                            <div class="mt-1 text-2xl font-bold">
                                <span class="text-emerald-600 dark:text-emerald-400">{{ stats.total_wins }}</span>
                                <span class="mx-1 text-neutral-400 text-lg">–</span>
                                <span class="text-red-500">{{ stats.total_losses }}</span>
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-neutral-500 uppercase tracking-wide">League</div>
                            <div class="mt-1 text-2xl font-bold">
                                <span class="text-emerald-600 dark:text-emerald-400">{{ stats.league_wins }}</span>
                                <span class="mx-1 text-neutral-400 text-lg">–</span>
                                <span class="text-red-500">{{ stats.league_losses }}</span>
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-neutral-500 uppercase tracking-wide">Friendly</div>
                            <div class="mt-1 text-2xl font-bold">
                                <span class="text-emerald-600 dark:text-emerald-400">{{ stats.friendly_wins }}</span>
                                <span class="mx-1 text-neutral-400 text-lg">–</span>
                                <span class="text-red-500">{{ stats.friendly_losses }}</span>
                            </div>
                        </div>
                        <div v-if="stats.total_wins + stats.total_losses > 0">
                            <div class="text-xs text-neutral-500 uppercase tracking-wide">Win %</div>
                            <div class="mt-1 text-2xl font-bold">
                                {{ Math.round((stats.total_wins / (stats.total_wins + stats.total_losses)) * 100) }}%
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Rating history chart -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Rating History</CardTitle>
                    <div data-slot="card-action" class="flex gap-1">
                        <button
                            v-for="opt in (['both', 'league', 'friendly'] as const)"
                            :key="opt"
                            class="rounded-md border px-3 py-1 text-xs capitalize"
                            :class="chartLine === opt ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900' : 'text-neutral-500'"
                            @click="chartLine = opt"
                        >{{ opt }}</button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="!hasData" class="py-12 text-center text-sm text-neutral-400">
                        No rating history yet.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <svg
                            :viewBox="`0 0 ${W} ${H}`"
                            class="w-full"
                            :style="{ minWidth: '300px', height: '200px' }"
                            aria-label="Rating history chart"
                        >
                            <!-- Y grid lines + labels -->
                            <g v-for="tick in yTicks" :key="tick">
                                <line
                                    :x1="PAD.left"
                                    :x2="W - PAD.right"
                                    :y1="ratingToY(tick)"
                                    :y2="ratingToY(tick)"
                                    stroke="currentColor"
                                    stroke-opacity="0.1"
                                    stroke-width="1"
                                />
                                <text
                                    :x="PAD.left - 6"
                                    :y="ratingToY(tick)"
                                    text-anchor="end"
                                    dominant-baseline="middle"
                                    class="fill-neutral-400 text-[10px]"
                                    font-size="10"
                                >{{ tick }}</text>
                            </g>

                            <!-- X axis date labels -->
                            <g v-for="d in xTicks" :key="d">
                                <text
                                    :x="dateToX(d)"
                                    :y="H - PAD.bottom + 14"
                                    text-anchor="middle"
                                    class="fill-neutral-400 text-[9px]"
                                    font-size="9"
                                >{{ d.slice(5) }}</text>
                            </g>

                            <!-- League line -->
                            <polyline
                                v-if="chartLine !== 'friendly' && leagueLine"
                                :points="leagueLine"
                                fill="none"
                                stroke="#3b82f6"
                                stroke-width="2"
                                stroke-linejoin="round"
                                stroke-linecap="round"
                            />
                            <!-- League dots -->
                            <circle
                                v-if="chartLine !== 'friendly'"
                                v-for="p in leagueHistory"
                                :key="p.date + p.rating + 'l'"
                                :cx="dateToX(p.date)"
                                :cy="ratingToY(p.rating)"
                                r="3"
                                fill="#3b82f6"
                            />

                            <!-- Friendly line -->
                            <polyline
                                v-if="chartLine !== 'league' && friendlyLine"
                                :points="friendlyLine"
                                fill="none"
                                stroke="#f59e0b"
                                stroke-width="2"
                                stroke-linejoin="round"
                                stroke-linecap="round"
                            />
                            <!-- Friendly dots -->
                            <circle
                                v-if="chartLine !== 'league'"
                                v-for="p in friendlyHistory"
                                :key="p.date + p.rating + 'f'"
                                :cx="dateToX(p.date)"
                                :cy="ratingToY(p.rating)"
                                r="3"
                                fill="#f59e0b"
                            />
                        </svg>

                        <!-- Legend -->
                        <div class="mt-2 flex gap-4 text-xs text-neutral-500">
                            <span v-if="chartLine !== 'friendly'" class="flex items-center gap-1.5">
                                <span class="inline-block h-2.5 w-4 rounded-full bg-blue-500"></span> League
                            </span>
                            <span v-if="chartLine !== 'league'" class="flex items-center gap-1.5">
                                <span class="inline-block h-2.5 w-4 rounded-full bg-amber-400"></span> Friendly
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </main>
    </div>
</template>

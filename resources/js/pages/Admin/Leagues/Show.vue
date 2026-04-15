<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Pencil, UserMinus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import PlayerPicker from '@/components/PlayerPicker.vue';

type League = { id: number; name: string; description: string | null; skill_level: string | null };
type PlayerLite = { id: number; first_name: string; last_name: string; email?: string };
type SeasonLite = { id: number; name: string; start_date: string; end_date: string };

const props = defineProps<{
    league: League;
    members: PlayerLite[];
    availablePlayers: PlayerLite[];
    seasons: SeasonLite[];
}>();

const selected = ref<number>(0);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin/leagues' },
            { title: 'Leagues', href: '/admin/leagues' },
        ],
    },
});

function attach() {
    if (!selected.value) return;
    router.post(`/admin/leagues/${props.league.id}/players`, { player_id: selected.value }, {
        onSuccess: () => (selected.value = 0),
    });
}

function detach(playerId: number) {
    router.delete(`/admin/leagues/${props.league.id}/players/${playerId}`);
}
</script>

<template>
    <Head :title="league.name" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle>{{ league.name }}</CardTitle>
                <Link :href="`/admin/leagues/${league.id}/edit`">
                    <Button size="icon-sm" variant="ghost" title="Edit league"><Pencil /></Button>
                </Link>
            </CardHeader>
            <CardContent class="space-y-2 text-sm">
                <div><span class="text-muted-foreground">Skill level:</span> {{ league.skill_level ?? '—' }}</div>
                <div><span class="text-muted-foreground">Description:</span> {{ league.description ?? '—' }}</div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Roster</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="mb-4 flex items-center gap-2">
                    <div class="flex-1 max-w-sm">
                        <PlayerPicker
                            v-model="selected"
                            :players="availablePlayers"
                            label="Add player to league"
                            placeholder="Add player…"
                        />
                    </div>
                    <Button size="sm" :disabled="!selected" @click="attach">Add</Button>
                </div>
                <ul class="divide-y text-sm">
                    <li v-for="m in members" :key="m.id" class="flex items-center justify-between py-2">
                        <span>
                            <Link :href="`/admin/players/${m.id}/edit`" class="underline">{{ m.first_name }} {{ m.last_name }}</Link>
                            <span class="text-muted-foreground"> {{ m.email }}</span>
                        </span>
                        <Button size="icon-sm" variant="ghost" title="Remove from league" @click="detach(m.id)"><UserMinus /></Button>
                    </li>
                    <li v-if="members.length === 0" class="py-4 text-center text-muted-foreground">No players in this league.</li>
                </ul>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle>Seasons</CardTitle>
                <Link :href="`/admin/seasons/create?league_id=${league.id}`"><Button size="sm">New Season</Button></Link>
            </CardHeader>
            <CardContent>
                <ul class="divide-y text-sm">
                    <li v-for="s in seasons" :key="s.id" class="flex items-center justify-between py-2">
                        <Link :href="`/admin/seasons/${s.id}`" class="underline">{{ s.name }}</Link>
                        <span class="text-muted-foreground">{{ s.start_date }} → {{ s.end_date }}</span>
                    </li>
                    <li v-if="seasons.length === 0" class="py-4 text-center text-muted-foreground">No seasons yet.</li>
                </ul>
            </CardContent>
        </Card>
    </div>
</template>

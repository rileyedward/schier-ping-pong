<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import MatchScoreDialog from '@/components/MatchScoreDialog.vue';

type PlayerLite = { id: number; first_name: string; last_name: string; email?: string };
type TeamPlayer = { id: number; name: string };
type Team = { id: number; name: string; players: TeamPlayer[] };
type Season = {
    id: number;
    name: string;
    description: string | null;
    start_date: string | null;
    end_date: string | null;
    format: 'singles' | 'doubles';
    league: { id: number; name: string } | null;
};
type Match = {
    id: number;
    scheduled_for: string | null;
    best_of: number;
    player_one_id: number | null;
    player_two_id: number | null;
    player_one: string | null;
    player_two: string | null;
    team_one_id: number | null;
    team_two_id: number | null;
    team_one: string | null;
    team_two: string | null;
    winner_team_id: number | null;
    played_at: string | null;
};

const props = defineProps<{
    season: Season;
    roster: PlayerLite[];
    availablePlayers: PlayerLite[];
    teams: Team[];
    matches: Match[];
}>();

const isDoubles = computed(() => props.season.format === 'doubles');

const selected = ref<number | ''>('');
const editingId = ref<number | null>(null);
const showNewMatch = ref(false);
const scoringMatch = ref<Match | null>(null);
const scoreOpen = ref(false);
const showNewTeam = ref(false);

function openScoreDialog(m: Match) {
    scoringMatch.value = m;
    scoreOpen.value = true;
}

const newMatch = useForm({
    player_one_id: '' as number | '',
    player_two_id: '' as number | '',
    team_one_id: '' as number | '',
    team_two_id: '' as number | '',
    scheduled_for: '',
    best_of: 3 as 3 | 5,
});

const editMatch = useForm({
    player_one_id: 0,
    player_two_id: 0,
    team_one_id: 0,
    team_two_id: 0,
    scheduled_for: '',
    best_of: 3 as 3 | 5,
});

const newTeam = useForm({
    name: '',
    player_ids: [0, 0] as [number, number],
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin/seasons' },
            { title: 'Seasons', href: '/admin/seasons' },
        ],
    },
});

function attach() {
    if (!selected.value) return;
    router.post(`/admin/seasons/${props.season.id}/players`, { player_id: selected.value }, {
        onSuccess: () => (selected.value = ''),
    });
}

function detach(playerId: number) {
    router.delete(`/admin/seasons/${props.season.id}/players/${playerId}`);
}

function scheduleMatch() {
    newMatch.post(`/admin/seasons/${props.season.id}/matches`, {
        preserveScroll: true,
        onSuccess: () => {
            newMatch.reset();
            showNewMatch.value = false;
        },
    });
}

function startEdit(m: Match) {
    editingId.value = m.id;
    if (isDoubles.value) {
        editMatch.team_one_id = m.team_one_id ?? 0;
        editMatch.team_two_id = m.team_two_id ?? 0;
    } else {
        editMatch.player_one_id = m.player_one_id ?? 0;
        editMatch.player_two_id = m.player_two_id ?? 0;
    }
    editMatch.scheduled_for = m.scheduled_for ?? '';
    editMatch.best_of = m.best_of as 3 | 5;
    editMatch.clearErrors();
}

function saveEdit(id: number) {
    editMatch.put(`/admin/matches/${id}`, {
        preserveScroll: true,
        onSuccess: () => (editingId.value = null),
    });
}

function generateSchedule() {
    const msg = props.matches.length
        ? 'This will delete all unplayed matches and regenerate the full round-robin schedule. Continue?'
        : isDoubles.value
          ? 'Generate a round-robin schedule for all teams?'
          : 'Generate a round-robin schedule for the current roster?';
    if (!confirm(msg)) return;
    router.post(`/admin/seasons/${props.season.id}/matches/generate`, {}, { preserveScroll: true });
}

function removeMatch(id: number) {
    if (!confirm('Remove this match?')) return;
    router.delete(`/admin/matches/${id}`, { preserveScroll: true });
}

function createTeam() {
    newTeam.post(`/admin/seasons/${props.season.id}/teams`, {
        preserveScroll: true,
        onSuccess: () => {
            newTeam.reset();
            newTeam.player_ids = [0, 0];
            showNewTeam.value = false;
        },
    });
}

function removeTeam(id: number) {
    if (!confirm('Remove this team? Associated unplayed matches will lose their team reference.')) return;
    router.delete(`/admin/seasons/${props.season.id}/teams/${id}`, { preserveScroll: true });
}
</script>

<template>
    <Head :title="season.name" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle>{{ season.name }}</CardTitle>
                <Link :href="`/admin/seasons/${season.id}/edit`"><Button size="sm" variant="outline">Edit</Button></Link>
            </CardHeader>
            <CardContent class="space-y-1 text-sm">
                <div><span class="text-muted-foreground">League:</span>
                    <Link v-if="season.league" :href="`/admin/leagues/${season.league.id}`" class="ml-1 underline">{{ season.league.name }}</Link>
                </div>
                <div><span class="text-muted-foreground">Dates:</span> {{ season.start_date }} → {{ season.end_date }}</div>
                <div><span class="text-muted-foreground">Format:</span> {{ season.format === 'doubles' ? 'Doubles' : 'Singles' }}</div>
                <div v-if="season.description"><span class="text-muted-foreground">Description:</span> {{ season.description }}</div>
            </CardContent>
        </Card>

        <!-- Teams card (doubles only) -->
        <Card v-if="isDoubles">
            <CardHeader>
                <CardTitle>Teams</CardTitle>
                <div data-slot="card-action">
                    <Button size="sm" @click="showNewTeam = !showNewTeam">
                        {{ showNewTeam ? 'Cancel' : '+ Create team' }}
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                <form v-if="showNewTeam" class="mb-4 space-y-2 rounded-md border p-3" @submit.prevent="createTeam">
                    <div>
                        <Label class="text-xs">Team name</Label>
                        <Input v-model="newTeam.name" placeholder="e.g. Team Alpha" />
                        <InputError :message="newTeam.errors.name" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <Label class="text-xs">Player 1</Label>
                            <select v-model.number="newTeam.player_ids[0]" class="h-9 w-full rounded-md border bg-transparent px-2 text-sm">
                                <option :value="0">—</option>
                                <option v-for="p in roster" :key="p.id" :value="p.id">{{ p.first_name }} {{ p.last_name }}</option>
                            </select>
                        </div>
                        <div>
                            <Label class="text-xs">Player 2</Label>
                            <select v-model.number="newTeam.player_ids[1]" class="h-9 w-full rounded-md border bg-transparent px-2 text-sm">
                                <option :value="0">—</option>
                                <option v-for="p in roster" :key="p.id" :value="p.id">{{ p.first_name }} {{ p.last_name }}</option>
                            </select>
                        </div>
                    </div>
                    <InputError :message="newTeam.errors.player_ids" />
                    <Button type="submit" size="sm" :disabled="newTeam.processing">Create team</Button>
                </form>

                <ul class="divide-y text-sm">
                    <li v-for="t in teams" :key="t.id" class="flex items-center justify-between py-2">
                        <div>
                            <span class="font-medium">{{ t.name }}</span>
                            <span class="ml-2 text-xs text-muted-foreground">
                                {{ t.players.map((p) => p.name).join(' & ') }}
                            </span>
                        </div>
                        <Button size="sm" variant="ghost" @click="removeTeam(t.id)">Remove</Button>
                    </li>
                    <li v-if="!teams.length" class="py-4 text-center text-muted-foreground">No teams yet.</li>
                </ul>
            </CardContent>
        </Card>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Roster</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="mb-4 flex items-center gap-2">
                        <select v-model="selected" class="h-9 flex-1 rounded-md border bg-transparent px-2 text-sm">
                            <option value="">Add player…</option>
                            <option v-for="p in availablePlayers" :key="p.id" :value="p.id">
                                {{ p.first_name }} {{ p.last_name }}
                            </option>
                        </select>
                        <Button size="sm" :disabled="!selected" @click="attach">Add</Button>
                    </div>
                    <ul class="divide-y text-sm">
                        <li v-for="m in roster" :key="m.id" class="flex items-center justify-between py-2">
                            <span>{{ m.first_name }} {{ m.last_name }}</span>
                            <Button size="sm" variant="ghost" @click="detach(m.id)">Remove</Button>
                        </li>
                        <li v-if="roster.length === 0" class="py-4 text-center text-muted-foreground">No players in this season.</li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Matches</CardTitle>
                    <div data-slot="card-action" class="flex flex-wrap gap-2">
                        <Button size="sm" @click="showNewMatch = !showNewMatch">
                            {{ showNewMatch ? 'Cancel' : '+ Schedule match' }}
                        </Button>
                        <Button size="sm" variant="outline" @click="generateSchedule">Generate schedule</Button>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <form v-if="showNewMatch" class="grid gap-2 rounded-md border p-3" @submit.prevent="scheduleMatch">
                        <div class="text-sm font-medium">Schedule a match</div>

                        <!-- Singles player selects -->
                        <template v-if="!isDoubles">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <Label for="p1" class="text-xs">Player 1</Label>
                                    <select id="p1" v-model="newMatch.player_one_id" class="h-9 w-full rounded-md border bg-transparent px-2 text-sm">
                                        <option value="">—</option>
                                        <option v-for="p in roster" :key="p.id" :value="p.id">{{ p.first_name }} {{ p.last_name }}</option>
                                    </select>
                                    <InputError :message="newMatch.errors.player_one_id" />
                                </div>
                                <div>
                                    <Label for="p2" class="text-xs">Player 2</Label>
                                    <select id="p2" v-model="newMatch.player_two_id" class="h-9 w-full rounded-md border bg-transparent px-2 text-sm">
                                        <option value="">—</option>
                                        <option v-for="p in roster" :key="p.id" :value="p.id">{{ p.first_name }} {{ p.last_name }}</option>
                                    </select>
                                    <InputError :message="newMatch.errors.player_two_id" />
                                </div>
                            </div>
                        </template>

                        <!-- Doubles team selects -->
                        <template v-else>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <Label for="t1" class="text-xs">Team 1</Label>
                                    <select id="t1" v-model="newMatch.team_one_id" class="h-9 w-full rounded-md border bg-transparent px-2 text-sm">
                                        <option value="">—</option>
                                        <option v-for="t in teams" :key="t.id" :value="t.id">{{ t.name }}</option>
                                    </select>
                                    <InputError :message="newMatch.errors.team_one_id" />
                                </div>
                                <div>
                                    <Label for="t2" class="text-xs">Team 2</Label>
                                    <select id="t2" v-model="newMatch.team_two_id" class="h-9 w-full rounded-md border bg-transparent px-2 text-sm">
                                        <option value="">—</option>
                                        <option v-for="t in teams" :key="t.id" :value="t.id">{{ t.name }}</option>
                                    </select>
                                    <InputError :message="newMatch.errors.team_two_id" />
                                </div>
                            </div>
                        </template>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <Label for="sf" class="text-xs">Scheduled for</Label>
                                <Input id="sf" type="date" v-model="newMatch.scheduled_for" />
                                <InputError :message="newMatch.errors.scheduled_for" />
                            </div>
                            <div>
                                <Label for="bo" class="text-xs">Format</Label>
                                <select id="bo" v-model.number="newMatch.best_of" class="h-9 w-full rounded-md border bg-transparent px-2 text-sm">
                                    <option :value="3">Best of 3</option>
                                    <option :value="5">Best of 5</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <Button type="submit" size="sm" :disabled="newMatch.processing">Schedule</Button>
                        </div>
                    </form>

                    <ul class="divide-y text-sm">
                        <li v-for="m in matches" :key="m.id" class="py-2">
                            <div v-if="editingId !== m.id" class="flex items-center justify-between gap-2">
                                <div>
                                    <div class="font-medium">
                                        <template v-if="m.team_one">{{ m.team_one }} vs {{ m.team_two }}</template>
                                        <template v-else>{{ m.player_one }} vs {{ m.player_two }}</template>
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ m.scheduled_for }} · Best of {{ m.best_of }}
                                        <span v-if="m.played_at"> · played {{ m.played_at }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <Button size="sm" @click="openScoreDialog(m)">
                                        {{ m.played_at ? 'Edit score' : 'Input match score' }}
                                    </Button>
                                    <Button size="sm" variant="outline" @click="startEdit(m)">Edit</Button>
                                    <Button size="sm" variant="destructive" @click="removeMatch(m.id)">Remove</Button>
                                </div>
                            </div>
                            <div v-else class="space-y-2 rounded-md border p-3">
                                <template v-if="!isDoubles">
                                    <div class="grid grid-cols-2 gap-2">
                                        <select v-model="editMatch.player_one_id" class="h-9 rounded-md border bg-transparent px-2 text-sm">
                                            <option v-for="p in roster" :key="p.id" :value="p.id">{{ p.first_name }} {{ p.last_name }}</option>
                                        </select>
                                        <select v-model="editMatch.player_two_id" class="h-9 rounded-md border bg-transparent px-2 text-sm">
                                            <option v-for="p in roster" :key="p.id" :value="p.id">{{ p.first_name }} {{ p.last_name }}</option>
                                        </select>
                                    </div>
                                    <InputError :message="editMatch.errors.player_one_id" />
                                </template>
                                <template v-else>
                                    <div class="grid grid-cols-2 gap-2">
                                        <select v-model="editMatch.team_one_id" class="h-9 rounded-md border bg-transparent px-2 text-sm">
                                            <option v-for="t in teams" :key="t.id" :value="t.id">{{ t.name }}</option>
                                        </select>
                                        <select v-model="editMatch.team_two_id" class="h-9 rounded-md border bg-transparent px-2 text-sm">
                                            <option v-for="t in teams" :key="t.id" :value="t.id">{{ t.name }}</option>
                                        </select>
                                    </div>
                                    <InputError :message="editMatch.errors.team_one_id" />
                                </template>
                                <div class="grid grid-cols-2 gap-2">
                                    <Input type="date" v-model="editMatch.scheduled_for" />
                                    <select v-model.number="editMatch.best_of" class="h-9 rounded-md border bg-transparent px-2 text-sm">
                                        <option :value="3">Best of 3</option>
                                        <option :value="5">Best of 5</option>
                                    </select>
                                </div>
                                <div class="flex gap-2">
                                    <Button size="sm" :disabled="editMatch.processing" @click="saveEdit(m.id)">Save</Button>
                                    <Button size="sm" variant="ghost" @click="editingId = null">Cancel</Button>
                                </div>
                            </div>
                        </li>
                        <li v-if="matches.length === 0" class="py-4 text-center text-muted-foreground">No matches scheduled yet.</li>
                    </ul>
                </CardContent>
            </Card>
        </div>

        <MatchScoreDialog v-model:open="scoreOpen" :match="scoringMatch" />
    </div>
</template>

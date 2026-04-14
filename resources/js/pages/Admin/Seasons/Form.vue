<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

type Season = {
    id: number;
    league_id: number;
    name: string;
    description: string | null;
    start_date: string;
    end_date: string;
    format: 'singles' | 'doubles';
} | null;

const props = defineProps<{
    season: Season;
    leagues: { id: number; name: string }[];
    preselectedLeagueId: number | null;
}>();

const isEdit = computed(() => !!props.season);

const form = useForm({
    league_id: props.season?.league_id ?? props.preselectedLeagueId ?? ('' as number | ''),
    name: props.season?.name ?? '',
    description: props.season?.description ?? '',
    start_date: props.season?.start_date ?? '',
    end_date: props.season?.end_date ?? '',
    format: props.season?.format ?? 'singles' as 'singles' | 'doubles',
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin/seasons' },
            { title: 'Seasons', href: '/admin/seasons' },
            { title: 'Form', href: '#' },
        ],
    },
});

function submit() {
    if (isEdit.value) form.put(`/admin/seasons/${props.season!.id}`);
    else form.post('/admin/seasons');
}
</script>

<template>
    <Head :title="isEdit ? 'Edit Season' : 'New Season'" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>{{ isEdit ? 'Edit Season' : 'New Season' }}</CardTitle>
            </CardHeader>
            <CardContent>
                <form class="space-y-4" @submit.prevent="submit">
                    <div v-if="!isEdit">
                        <Label for="league_id">League</Label>
                        <select
                            id="league_id"
                            v-model="form.league_id"
                            class="flex h-9 w-full rounded-md border bg-transparent px-2 text-sm shadow-xs outline-none focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                        >
                            <option value="">Select a league…</option>
                            <option v-for="l in leagues" :key="l.id" :value="l.id">{{ l.name }}</option>
                        </select>
                        <InputError :message="form.errors.league_id" />
                        <p class="mt-1 text-xs text-muted-foreground">All current league members will be added to the season roster.</p>
                    </div>
                    <div>
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" placeholder="e.g. Spring 2026" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <Label for="start_date">Start date</Label>
                            <Input id="start_date" type="date" v-model="form.start_date" />
                            <InputError :message="form.errors.start_date" />
                        </div>
                        <div>
                            <Label for="end_date">End date</Label>
                            <Input id="end_date" type="date" v-model="form.end_date" />
                            <InputError :message="form.errors.end_date" />
                        </div>
                    </div>
                    <div>
                        <Label>Season format</Label>
                        <div class="mt-2 flex gap-4 text-sm">
                            <label class="flex items-center gap-2">
                                <input type="radio" value="singles" v-model="form.format" />
                                Singles
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" value="doubles" v-model="form.format" />
                                Doubles
                            </label>
                        </div>
                        <InputError :message="form.errors.format" />
                    </div>
                    <div>
                        <Label for="description">Description</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            class="flex min-h-20 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                        />
                        <InputError :message="form.errors.description" />
                    </div>
                    <div class="flex gap-2">
                        <Button type="submit" :disabled="form.processing">Save</Button>
                        <a href="/admin/seasons"><Button type="button" variant="outline">Cancel</Button></a>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>

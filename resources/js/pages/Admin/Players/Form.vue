<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

type Player = { id: number; first_name: string; last_name: string; email: string } | null;
type LeagueOption = { id: number; name: string };

const props = defineProps<{
    player: Player;
    leagues: LeagueOption[];
    selectedLeagueIds: number[];
}>();

const isEdit = computed(() => !!props.player);

const form = useForm({
    first_name: props.player?.first_name ?? '',
    last_name: props.player?.last_name ?? '',
    email: props.player?.email ?? '',
    password: '',
    league_ids: [...(props.selectedLeagueIds ?? [])],
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin/players' },
            { title: 'Players', href: '/admin/players' },
            { title: 'Form', href: '#' },
        ],
    },
});

function submit() {
    if (isEdit.value) {
        form.put(`/admin/players/${props.player!.id}`);
    } else {
        form.post('/admin/players');
    }
}

function toggleLeague(id: number) {
    const i = form.league_ids.indexOf(id);
    if (i === -1) form.league_ids.push(id);
    else form.league_ids.splice(i, 1);
}
</script>

<template>
    <Head :title="isEdit ? 'Edit Player' : 'New Player'" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>{{ isEdit ? 'Edit Player' : 'New Player' }}</CardTitle>
            </CardHeader>
            <CardContent>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <Label for="first_name">First name</Label>
                            <Input id="first_name" v-model="form.first_name" />
                            <InputError :message="form.errors.first_name" />
                        </div>
                        <div>
                            <Label for="last_name">Last name</Label>
                            <Input id="last_name" v-model="form.last_name" />
                            <InputError :message="form.errors.last_name" />
                        </div>
                    </div>
                    <div>
                        <Label for="email">Email</Label>
                        <Input id="email" type="email" v-model="form.email" />
                        <InputError :message="form.errors.email" />
                    </div>
                    <div>
                        <Label for="password">{{ isEdit ? 'New password (leave blank to keep)' : 'Temporary password' }}</Label>
                        <Input id="password" type="password" v-model="form.password" />
                        <InputError :message="form.errors.password" />
                    </div>
                    <div>
                        <Label>Leagues</Label>
                        <div class="mt-2 space-y-1">
                            <label v-for="l in leagues" :key="l.id" class="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    :checked="form.league_ids.includes(l.id)"
                                    @change="toggleLeague(l.id)"
                                />
                                {{ l.name }}
                            </label>
                            <p v-if="leagues.length === 0" class="text-sm text-muted-foreground">No leagues yet.</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button type="submit" :disabled="form.processing">Save</Button>
                        <a href="/admin/players"><Button type="button" variant="outline">Cancel</Button></a>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>

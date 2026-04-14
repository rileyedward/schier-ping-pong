<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type LeagueRow = { id: number; name: string; skill_level: string | null; players_count: number };

defineProps<{ leagues: LeagueRow[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin/leagues' },
            { title: 'Leagues', href: '/admin/leagues' },
        ],
    },
});

function destroy(id: number) {
    if (!confirm('Delete this league?')) return;
    router.delete(`/admin/leagues/${id}`);
}
</script>

<template>
    <Head title="Leagues" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle>Leagues</CardTitle>
                <Link href="/admin/leagues/create"><Button>New League</Button></Link>
            </CardHeader>
            <CardContent>
                <table class="w-full text-sm">
                    <thead class="text-left text-muted-foreground">
                        <tr>
                            <th class="py-2">Name</th>
                            <th class="py-2">Skill level</th>
                            <th class="py-2">Players</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="l in leagues" :key="l.id" class="border-t">
                            <td class="py-2">
                                <Link :href="`/admin/leagues/${l.id}`" class="underline">{{ l.name }}</Link>
                            </td>
                            <td class="py-2">{{ l.skill_level ?? '—' }}</td>
                            <td class="py-2">{{ l.players_count }}</td>
                            <td class="py-2">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="`/admin/leagues/${l.id}/edit`">
                                        <Button size="icon-sm" variant="ghost" title="Edit league"><Pencil /></Button>
                                    </Link>
                                    <Button size="icon-sm" variant="destructive" title="Delete league" @click="destroy(l.id)"><Trash2 /></Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="leagues.length === 0">
                            <td colspan="4" class="py-6 text-center text-muted-foreground">No leagues yet.</td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>
</template>

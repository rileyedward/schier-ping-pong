<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type SeasonRow = {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    players_count: number;
    league: { id: number; name: string } | null;
};

defineProps<{ seasons: SeasonRow[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin/seasons' },
            { title: 'Seasons', href: '/admin/seasons' },
        ],
    },
});

function destroy(id: number) {
    if (!confirm('Delete this season?')) return;
    router.delete(`/admin/seasons/${id}`);
}
</script>

<template>
    <Head title="Seasons" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle>Seasons</CardTitle>
                <Link href="/admin/seasons/create"><Button>New Season</Button></Link>
            </CardHeader>
            <CardContent>
                <table class="w-full text-sm">
                    <thead class="text-left text-muted-foreground">
                        <tr>
                            <th class="py-2">Name</th>
                            <th class="py-2">League</th>
                            <th class="py-2">Dates</th>
                            <th class="py-2">Players</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in seasons" :key="s.id" class="border-t">
                            <td class="py-2">
                                <Link :href="`/admin/seasons/${s.id}`" class="underline">{{ s.name }}</Link>
                            </td>
                            <td class="py-2">{{ s.league?.name ?? '—' }}</td>
                            <td class="py-2">{{ s.start_date }} → {{ s.end_date }}</td>
                            <td class="py-2">{{ s.players_count }}</td>
                            <td class="py-2 text-right">
                                <Link :href="`/admin/seasons/${s.id}/edit`">
                                    <Button size="sm" variant="outline">Edit</Button>
                                </Link>
                                <Button size="sm" variant="destructive" class="ml-2" @click="destroy(s.id)">Delete</Button>
                            </td>
                        </tr>
                        <tr v-if="seasons.length === 0">
                            <td colspan="5" class="py-6 text-center text-muted-foreground">No seasons yet.</td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>
</template>

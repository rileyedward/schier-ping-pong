<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type PlayerRow = {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    leagues_count: number;
};

defineProps<{ players: PlayerRow[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin/players' },
            { title: 'Players', href: '/admin/players' },
        ],
    },
});

function destroy(id: number) {
    if (!confirm('Delete this player?')) return;
    router.delete(`/admin/players/${id}`);
}
</script>

<template>
    <Head title="Players" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle>Players</CardTitle>
                <Link href="/admin/players/create">
                    <Button>New Player</Button>
                </Link>
            </CardHeader>
            <CardContent>
                <table class="w-full text-sm">
                    <thead class="text-left text-muted-foreground">
                        <tr>
                            <th class="py-2">Name</th>
                            <th class="py-2">Email</th>
                            <th class="py-2">Leagues</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in players" :key="p.id" class="border-t">
                            <td class="py-2">{{ p.first_name }} {{ p.last_name }}</td>
                            <td class="py-2">{{ p.email }}</td>
                            <td class="py-2">{{ p.leagues_count }}</td>
                            <td class="py-2 text-right">
                                <Link :href="`/admin/players/${p.id}/edit`">
                                    <Button size="sm" variant="outline">Edit</Button>
                                </Link>
                                <Button size="sm" variant="destructive" class="ml-2" @click="destroy(p.id)">Delete</Button>
                            </td>
                        </tr>
                        <tr v-if="players.length === 0">
                            <td colspan="4" class="py-6 text-center text-muted-foreground">No players yet.</td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>
</template>

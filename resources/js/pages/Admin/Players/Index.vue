<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from 'lucide-vue-next';
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
                            <td class="py-2">
                                <Link :href="`/admin/players/${p.id}/edit`" class="underline">{{ p.first_name }} {{ p.last_name }}</Link>
                            </td>
                            <td class="py-2">{{ p.email }}</td>
                            <td class="py-2">{{ p.leagues_count }}</td>
                            <td class="py-2">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="`/admin/players/${p.id}/edit`">
                                        <Button size="icon-sm" variant="ghost" title="Edit player"><Pencil /></Button>
                                    </Link>
                                    <Button size="icon-sm" variant="destructive" title="Delete player" @click="destroy(p.id)"><Trash2 /></Button>
                                </div>
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

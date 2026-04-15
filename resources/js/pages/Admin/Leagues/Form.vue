<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type League = { id: number; name: string; description: string | null; skill_level: string | null; color: string | null } | null;

const props = defineProps<{ league: League }>();

const isEdit = computed(() => !!props.league);

const form = useForm({
    name: props.league?.name ?? '',
    description: props.league?.description ?? '',
    skill_level: props.league?.skill_level ?? '',
    color: props.league?.color ?? '#3f9c6b',
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin/leagues' },
            { title: 'Leagues', href: '/admin/leagues' },
            { title: 'Form', href: '#' },
        ],
    },
});

function submit() {
    if (isEdit.value) {
form.put(`/admin/leagues/${props.league!.id}`);
} else {
form.post('/admin/leagues');
}
}
</script>

<template>
    <Head :title="isEdit ? 'Edit League' : 'New League'" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>{{ isEdit ? 'Edit League' : 'New League' }}</CardTitle>
            </CardHeader>
            <CardContent>
                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div>
                        <Label for="skill_level">Skill level</Label>
                        <Input id="skill_level" v-model="form.skill_level" placeholder="e.g. Beginner, A Division" />
                        <InputError :message="form.errors.skill_level" />
                    </div>
                    <div>
                        <Label for="color">Color</Label>
                        <div class="flex items-center gap-2">
                            <input id="color" type="color" v-model="form.color" class="h-9 w-14 cursor-pointer rounded-md border bg-transparent" />
                            <Input v-model="form.color" placeholder="#3f9c6b" class="max-w-32" />
                        </div>
                        <InputError :message="form.errors.color" />
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
                        <a href="/admin/leagues"><Button type="button" variant="outline">Cancel</Button></a>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>

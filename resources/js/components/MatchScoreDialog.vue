<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

type MatchInput = {
    id: number;
    best_of: number;
    player_one_id: number;
    player_two_id: number;
    player_one: string;
    player_two: string;
};

const props = defineProps<{
    open: boolean;
    match: MatchInput | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'recorded'): void;
}>();

const form = useForm({
    winner_id: 0,
    games_played: 0,
});

const winTarget = computed(() => (props.match ? Math.floor(props.match.best_of / 2) + 1 : 2));
const gamesOptions = computed(() => {
    if (!props.match) return [];
    const out: number[] = [];
    for (let n = winTarget.value; n <= props.match.best_of; n++) out.push(n);
    return out;
});

watch(
    () => props.open,
    (open) => {
        if (open && props.match) {
            form.winner_id = props.match.player_one_id;
            form.games_played = winTarget.value;
            form.clearErrors();
        }
    },
);

function submit() {
    if (!props.match) return;
    form.post(`/admin/matches/${props.match.id}/score`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false);
            emit('recorded');
        },
    });
}

function setOpen(value: boolean) {
    emit('update:open', value);
}
</script>

<template>
    <Dialog :open="open" @update:open="setOpen">
        <DialogContent v-if="match">
            <DialogHeader>
                <DialogTitle>Input match score</DialogTitle>
                <DialogDescription>
                    {{ match.player_one }} vs {{ match.player_two }} · Best of {{ match.best_of }}
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <Label>Winner</Label>
                    <div class="mt-2 space-y-2">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" :value="match.player_one_id" v-model="form.winner_id" />
                            {{ match.player_one }}
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" :value="match.player_two_id" v-model="form.winner_id" />
                            {{ match.player_two }}
                        </label>
                    </div>
                    <InputError :message="form.errors.winner_id" />
                </div>

                <div>
                    <Label for="games_played">In how many games did the winner win?</Label>
                    <select
                        id="games_played"
                        v-model.number="form.games_played"
                        class="mt-1 flex h-9 w-full rounded-md border bg-transparent px-2 text-sm shadow-xs outline-none focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                    >
                        <option v-for="n in gamesOptions" :key="n" :value="n">
                            {{ n }} games ({{ winTarget }}–{{ n - winTarget }})
                        </option>
                    </select>
                    <InputError :message="form.errors.games_played" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="ghost" @click="setOpen(false)">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">Save score</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const digits = ref(['', '', '', '']);
const inputRefs = ref<HTMLInputElement[]>([]);
const shaking = ref(false);

const form = useForm({ code: '' });

const page = usePage();

function onInput(index: number, event: Event) {
    const target = event.target as HTMLInputElement;
    const val = target.value.replace(/\D/g, '').slice(-1);
    digits.value[index] = val;
    target.value = val;

    if (val && index < 3) {
        inputRefs.value[index + 1]?.focus();
    }

    if (digits.value.every((d) => d !== '')) {
        submit();
    }
}

function onKeydown(index: number, event: KeyboardEvent) {
    if (event.key === 'Backspace') {
        if (digits.value[index] === '' && index > 0) {
            digits.value[index - 1] = '';
            inputRefs.value[index - 1]?.focus();
        } else {
            digits.value[index] = '';
        }
    }
}

function submit() {
    form.code = digits.value.join('');
    form.post('/unlock', {
        onError: () => {
            shaking.value = true;
            digits.value = ['', '', '', ''];
            setTimeout(() => {
                shaking.value = false;
                inputRefs.value[0]?.focus();
            }, 600);
        },
    });
}

onMounted(() => {
    inputRefs.value[0]?.focus();
});
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-50 p-4">
        <div
            class="w-full max-w-sm rounded-2xl bg-white p-8 text-center shadow-lg"
        >
            <div class="mb-6">
                <h1 class="text-xl font-semibold text-gray-900">
                    Enter Access Code
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Enter the 4-digit code to continue
                </p>
            </div>

            <div
                :class="[
                    'mb-6 flex justify-center gap-3',
                    { 'animate-shake': shaking },
                ]"
            >
                <input
                    v-for="(digit, index) in digits"
                    :key="index"
                    :ref="
                        (el) => {
                            if (el) inputRefs[index] = el as HTMLInputElement;
                        }
                    "
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="1"
                    :value="digit"
                    autocomplete="off"
                    :class="[
                        'h-16 w-14 rounded-xl border-2 text-center text-2xl font-bold transition-all outline-none',
                        form.errors.code
                            ? 'border-red-400 bg-red-50 text-red-700'
                            : 'border-gray-200 bg-gray-50 text-gray-900 focus:border-gray-900 focus:bg-white',
                    ]"
                    @input="onInput(index, $event)"
                    @keydown="onKeydown(index, $event)"
                />
            </div>

            <p v-if="form.errors.code" class="mb-4 text-sm text-red-600">
                {{ form.errors.code }}
            </p>
        </div>
    </div>
</template>

<style scoped>
@keyframes shake {
    0%,
    100% {
        transform: translateX(0);
    }
    15% {
        transform: translateX(-6px);
    }
    30% {
        transform: translateX(6px);
    }
    45% {
        transform: translateX(-5px);
    }
    60% {
        transform: translateX(5px);
    }
    75% {
        transform: translateX(-3px);
    }
    90% {
        transform: translateX(3px);
    }
}

.animate-shake {
    animation: shake 0.5s ease-in-out;
}
</style>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { Check, Eye, EyeOff, Lock } from '@lucide/vue';
import { computed, ref } from 'vue';

const showCurrent = ref(false);
const showNew = ref(false);
const showConfirm = ref(false);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Lightweight client-side strength hint (server enforces the real rules).
const strength = computed(() => {
    const v = form.password;
    if (!v) return 0;
    let score = 0;
    if (v.length >= 8) score++;
    if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
    if (/\d/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    return score;
});

const strengthMeta = computed(() => {
    return [
        { label: '', color: 'grey-lighten-1' },
        { label: 'Заиф', color: 'red' },
        { label: 'Миёна', color: 'orange' },
        { label: 'Хуб', color: 'light-green' },
        { label: 'Қавӣ', color: 'green' },
    ][strength.value];
});

function updatePassword() {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
            }
            if (form.errors.current_password) {
                form.reset('current_password');
            }
        },
    });
}
</script>

<template>
    <v-form @submit.prevent="updatePassword">
        <v-text-field
            v-model="form.current_password"
            label="Рамзи ҷорӣ"
            :type="showCurrent ? 'text' : 'password'"
            variant="outlined"
            density="comfortable"
            color="indigo"
            autocomplete="current-password"
            :error-messages="form.errors.current_password"
            class="mb-2"
        >
            <template #prepend-inner>
                <Lock style="width: 18px; height: 18px;" class="text-grey-darken-1" />
            </template>
            <template #append-inner>
                <component
                    :is="showCurrent ? EyeOff : Eye"
                    style="width: 18px; height: 18px; cursor: pointer;"
                    class="text-grey-darken-1"
                    @click="showCurrent = !showCurrent"
                />
            </template>
        </v-text-field>

        <v-text-field
            v-model="form.password"
            label="Рамзи нав"
            :type="showNew ? 'text' : 'password'"
            variant="outlined"
            density="comfortable"
            color="indigo"
            autocomplete="new-password"
            :error-messages="form.errors.password"
            hide-details="auto"
            class="mb-2"
        >
            <template #prepend-inner>
                <Lock style="width: 18px; height: 18px;" class="text-grey-darken-1" />
            </template>
            <template #append-inner>
                <component
                    :is="showNew ? EyeOff : Eye"
                    style="width: 18px; height: 18px; cursor: pointer;"
                    class="text-grey-darken-1"
                    @click="showNew = !showNew"
                />
            </template>
        </v-text-field>

        <!-- Strength meter -->
        <div v-if="form.password" class="d-flex align-center ga-3 mb-4 px-1">
            <v-progress-linear
                :model-value="strength * 25"
                :color="strengthMeta.color"
                height="6"
                rounded
                class="flex-grow-1"
            />
            <span class="text-caption font-weight-bold" :class="`text-${strengthMeta.color}`">
                {{ strengthMeta.label }}
            </span>
        </div>

        <v-text-field
            v-model="form.password_confirmation"
            label="Тасдиқи рамз"
            :type="showConfirm ? 'text' : 'password'"
            variant="outlined"
            density="comfortable"
            color="indigo"
            autocomplete="new-password"
            :error-messages="form.errors.password_confirmation"
        >
            <template #prepend-inner>
                <Lock style="width: 18px; height: 18px;" class="text-grey-darken-1" />
            </template>
            <template #append-inner>
                <component
                    :is="showConfirm ? EyeOff : Eye"
                    style="width: 18px; height: 18px; cursor: pointer;"
                    class="text-grey-darken-1"
                    @click="showConfirm = !showConfirm"
                />
            </template>
        </v-text-field>

        <div class="d-flex align-center ga-4 mt-2">
            <v-btn
                type="submit"
                color="indigo"
                variant="flat"
                rounded="lg"
                class="font-weight-bold text-none text-white px-6"
                :loading="form.processing"
            >
                Захира кардан
            </v-btn>

            <v-fade-transition>
                <span v-if="form.recentlySuccessful" class="d-flex align-center text-green-darken-2 text-body-2 font-weight-medium">
                    <Check style="width: 16px; height: 16px;" class="mr-1" />
                    Захира шуд
                </span>
            </v-fade-transition>
        </div>
    </v-form>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { Check, Mail, User } from '@lucide/vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <v-form @submit.prevent="form.patch(route('profile.update'))">
        <v-text-field
            v-model="form.name"
            label="Ном"
            variant="outlined"
            density="comfortable"
            color="indigo"
            autocomplete="name"
            :error-messages="form.errors.name"
            class="mb-2"
        >
            <template #prepend-inner>
                <User style="width: 18px; height: 18px;" class="text-grey-darken-1" />
            </template>
        </v-text-field>

        <v-text-field
            v-model="form.email"
            label="Почтаи электронӣ"
            type="email"
            variant="outlined"
            density="comfortable"
            color="indigo"
            autocomplete="username"
            :error-messages="form.errors.email"
        >
            <template #prepend-inner>
                <Mail style="width: 18px; height: 18px;" class="text-grey-darken-1" />
            </template>
        </v-text-field>

        <v-alert
            v-if="mustVerifyEmail && user.email_verified_at === null"
            type="warning"
            variant="tonal"
            density="compact"
            class="mb-4 rounded-lg text-body-2"
        >
            Почтаи электронии шумо тасдиқ нашудааст.
            <Link
                :href="route('verification.send')"
                method="post"
                as="button"
                class="font-weight-bold text-decoration-underline"
            >
                Барои аз нав фиристодан зер кунед.
            </Link>
            <div
                v-show="status === 'verification-link-sent'"
                class="mt-2 font-weight-medium text-green-darken-2"
            >
                Истиноди нави тасдиқ фиристода шуд.
            </div>
        </v-alert>

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

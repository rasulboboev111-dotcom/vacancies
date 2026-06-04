<script setup>
import { useForm } from '@inertiajs/vue3';
import { AlertTriangle } from '@lucide/vue';

const props = defineProps({
    user: { type: Object, default: null },
});

const open = defineModel({ type: Boolean, default: false });

const form = useForm({});

function confirmDelete() {
    if (!props.user) {
        return;
    }
    form.delete(route('users.destroy', props.user.id), {
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <v-dialog v-model="open" max-width="480px" persistent>
        <v-card class="rounded-xl border pa-4">
            <v-card-title class="d-flex align-center font-weight-black text-error text-h5">
                <AlertTriangle style="width: 28px; height: 28px; margin-right: 12px;" class="text-error animate-pulse" />
                <span>Корбар нест карда шавад?</span>
            </v-card-title>

            <v-card-text class="py-4 text-body-1 text-grey-darken-3 font-weight-medium">
                Шумо мутмаин ҳастед, ки мехоҳед корбари <strong class="text-indigo-darken-4">{{ user?.name }}</strong>-ро нест кунед? Ӯ дастрасӣ ба системаро аз даст медиҳад. Ин амалро дар Сабад бекор кардан мумкин аст.
            </v-card-text>

            <v-divider class="my-2" />

            <v-card-actions class="px-2">
                <v-spacer />
                <v-btn variant="text" rounded="lg" class="px-4 font-weight-bold" @click="open = false">
                    Бекор кардан
                </v-btn>
                <v-btn
                    variant="flat"
                    rounded="lg"
                    class="px-5 font-weight-bold"
                    style="background-color: #d32f2f !important; color: #ffffff !important;"
                    :loading="form.processing"
                    @click="confirmDelete"
                >
                    Нест кардан
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

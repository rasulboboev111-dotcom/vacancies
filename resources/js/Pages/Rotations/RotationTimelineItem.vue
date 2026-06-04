<script setup>
import { ArrowDown, ArrowRight, Clock, Info } from '@lucide/vue';
import { formatDate } from '@/lib/date';

defineProps({
    rotation: { type: Object, required: true },
});
</script>

<template>
    <v-timeline-item dot-color="indigo" size="small" class="mb-6">
        <div class="d-flex justify-space-between align-center mb-1">
            <div>
                <span class="font-weight-black text-subtitle-1 text-indigo-darken-4">
                    {{ rotation.employee ? rotation.employee.full_name : 'Корманд несткардашуда' }}
                </span>
                <span class="text-caption text-grey ml-3 d-inline-flex align-center">
                    <Clock style="width: 12px; height: 12px; margin-right: 4px;" class="text-grey" />
                    {{ formatDate(rotation.rotation_date) }}
                </span>
            </div>
            <v-chip size="x-small" color="indigo" variant="outlined" class="font-weight-bold">
                Санаи ҷобаҷогузорӣ: {{ formatDate(rotation.rotation_date) }}
            </v-chip>
        </div>

        <!-- Rotation details visual board -->
        <v-row class="mt-2 pl-1 mb-2">
            <v-col cols="12" md="5" class="py-1">
                <v-card elevation="0" class="pa-3 rounded-lg border bg-surface-darken text-center border-l-4 border-error">
                    <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Таъиноти кӯҳна</span>
                    <div class="font-weight-bold text-body-2 text-indigo-darken-2 mt-1">
                        {{ rotation.old_position?.name || '-' }}
                    </div>
                    <div class="text-caption text-grey-darken-2 font-weight-medium">
                        Шуъба: {{ rotation.old_department?.name || '-' }}
                    </div>
                    <v-chip size="x-small" color="error" variant="tonal" class="mt-2 font-weight-bold text-uppercase">
                        {{ rotation.old_branch ? rotation.old_branch.name : 'Филиали несткардашуда' }}
                    </v-chip>
                </v-card>
            </v-col>

            <v-col cols="12" md="2" class="d-flex justify-center align-center py-2">
                <v-avatar color="indigo-lighten-5" size="40" class="border">
                    <ArrowRight style="width: 16px; height: 16px;" class="text-indigo hidden-sm-and-down" />
                    <ArrowDown style="width: 16px; height: 16px;" class="text-indigo hidden-md-and-up" />
                </v-avatar>
            </v-col>

            <v-col cols="12" md="5" class="py-1">
                <v-card elevation="0" class="pa-3 rounded-lg border bg-surface-darken text-center border-l-4 border-success">
                    <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Таъиноти нав</span>
                    <div class="font-weight-bold text-body-2 text-indigo-darken-4 mt-1">
                        {{ rotation.new_position?.name || '-' }}
                    </div>
                    <div class="text-caption text-grey-darken-2 font-weight-medium">
                        Шуъба: {{ rotation.new_department?.name || '-' }}
                    </div>
                    <v-chip size="x-small" color="success" variant="tonal" class="mt-2 font-weight-bold text-uppercase">
                        {{ rotation.new_branch ? rotation.new_branch.name : 'Филиали несткардашуда' }}
                    </v-chip>
                </v-card>
            </v-col>
        </v-row>

        <!-- Reason box -->
        <div v-if="rotation.reason" class="text-body-2 text-grey-darken-3 font-weight-medium bg-indigo-lighten-5 pa-3 rounded-lg border pl-4 border-l-4 border-indigo mt-3">
            <span class="d-flex align-center text-caption text-indigo font-weight-bold text-uppercase mb-1">
                <Info style="width: 16px; height: 16px; margin-right: 4px;" class="text-indigo" />
                Асос / Сабаби ҷобаҷогузорӣ:
            </span>
            {{ rotation.reason }}
        </div>
    </v-timeline-item>
</template>

<style scoped>
.bg-surface-darken {
    background-color: rgba(248, 250, 252, 0.9) !important;
}
.border-l-4 {
    border-left-width: 4px !important;
}
</style>

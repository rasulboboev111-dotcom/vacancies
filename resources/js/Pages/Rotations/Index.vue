<script setup>
import { Head, router } from '@inertiajs/vue3';
import { GitFork, RefreshCw, Trash2, TriangleAlert } from '@lucide/vue';
import { ref } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RotationTimelineItem from '@/Pages/Rotations/RotationTimelineItem.vue';

const props = defineProps({
    rotations: { type: Object, required: true },
});

const { isAdmin } = usePermissions();

function changePage(page) {
    router.get(route('rotations.index'), { page }, { preserveState: true });
}

// Clear-all confirmation flow (admin only, irreversible).
const clearDialog = ref(false);
const clearing = ref(false);

function clearRotations() {
    clearing.value = true;
    router.delete(route('rotations.clear'), {
        preserveScroll: true,
        onFinish: () => {
            clearing.value = false;
            clearDialog.value = false;
        },
    });
}
</script>

<template>
    <Head title="Таърихи ҷобаҷогузорӣ" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <RefreshCw style="width: 24px; height: 24px; margin-right: 12px;" class="text-indigo-accent-2" />
                <span>Ҷобаҷогузории кормандон</span>
            </div>
        </template>

        <!-- Intro banner: explains the concept + the colour legend -->
        <div class="rot-banner mb-6">
            <div class="rot-banner__main">
                <div>
                    <h2 class="rot-banner__title">
                        Таърихи ивазкунии вазифаҳо
                    </h2>
                    <p class="rot-banner__sub">
                        Ҳар сабт нишон медиҳад, ки корманд аз кадом <strong>вазифа, шуъба ё филиал</strong>
                        ба таъиноти нав гузаштааст.
                    </p>
                </div>
            </div>
            <div class="rot-legend">
                <span class="rot-legend__item">
                    <span class="rot-legend__dot rot-legend__dot--from" />
                    Таъиноти пешина
                </span>
                <span class="rot-legend__arrow">→</span>
                <span class="rot-legend__item">
                    <span class="rot-legend__dot rot-legend__dot--to" />
                    Таъиноти нав
                </span>
            </div>
            <span v-if="rotations.total" class="rot-banner__total">
                {{ rotations.total }}
                <em>сабт</em>
            </span>

            <button
                v-if="isAdmin && rotations.total"
                type="button"
                class="rot-clear"
                @click="clearDialog = true"
            >
                <Trash2 class="rot-clear__icon" />
                <span>Тоза кардан</span>
            </button>
        </div>

        <!-- Rotation cards -->
        <div v-if="rotations.data.length > 0" class="rot-list">
            <RotationTimelineItem
                v-for="(rotation, i) in rotations.data"
                :key="rotation.id"
                :rotation="rotation"
                :index="i"
            />
        </div>

        <!-- Empty state -->
        <div v-else class="rot-empty">
            <GitFork class="rot-empty__icon" />
            <p class="rot-empty__text">
                Ҳанӯз ягон ҷобаҷогузорӣ дар система ба қайд гирифта нашудааст.
            </p>
        </div>

        <!-- Pagination -->
        <div v-if="rotations.data.length > 0" class="rot-foot mt-2">
            <div class="rot-foot__count">
                Нишон дода шуд {{ rotations.from || 0 }} – {{ rotations.to || 0 }}
                аз {{ rotations.total || 0 }} сабт
            </div>
            <v-pagination
                v-if="rotations.last_page > 1"
                :model-value="rotations.current_page"
                :length="rotations.last_page"
                :total-visible="5"
                density="comfortable"
                rounded="lg"
                active-color="indigo"
                @update:model-value="changePage"
            />
        </div>

        <!-- Clear-all confirmation (admin) -->
        <v-dialog v-model="clearDialog" max-width="440px">
            <v-card class="rounded-xl overflow-hidden" elevation="8" style="background: #ffffff;">
                <div class="pa-6 text-center">
                    <v-avatar color="red-lighten-5" size="56" class="mb-3">
                        <TriangleAlert style="width: 28px; height: 28px; color: #e11d48;" />
                    </v-avatar>
                    <h3 class="text-h6 font-weight-bold mb-2" style="color: #1e293b;">
                        Тоза кардани таърихи ҷобаҷогузорӣ?
                    </h3>
                    <p class="text-body-2" style="color: #64748b;">
                        Ҳамаи <b style="color: #1e293b;">{{ rotations.total || 0 }}</b> сабти ҷобаҷогузорӣ нест карда мешавад.
                        Ин амал бебозгашт аст.
                    </p>
                </div>
                <v-divider />
                <v-card-actions class="pa-4">
                    <v-btn variant="text" rounded="lg" :disabled="clearing" style="color: #475569;" @click="clearDialog = false">
                        Бекор кардан
                    </v-btn>
                    <v-spacer />
                    <v-btn
                        variant="flat"
                        rounded="lg"
                        class="px-5 font-weight-bold"
                        style="background-color: #e11d48 !important; color: #ffffff !important;"
                        :loading="clearing"
                        @click="clearRotations"
                    >
                        <template #prepend>
                            <Trash2 style="width: 16px; height: 16px; color: #ffffff;" />
                        </template>
                        Бале, тоза кун
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </AuthenticatedLayout>
</template>

<style scoped>
/* ── Intro banner ── */
.rot-banner {
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
    padding: 22px 26px;
    border-radius: 20px;
    background:
        radial-gradient(120% 140% at 0% 0%, rgba(99, 102, 241, 0.1) 0%, rgba(99, 102, 241, 0) 55%),
        linear-gradient(135deg, #ffffff 0%, #f7f8fc 100%);
    border: 1px solid #e6eaf1;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.rot-banner__main {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: 1 1 360px;
    min-width: 0;
}
.rot-banner__title {
    margin: 0;
    font-size: 1.18rem;
    font-weight: 800;
    letter-spacing: -0.01em;
    color: #1e1b4b;
}
.rot-banner__sub {
    margin: 4px 0 0;
    font-size: 0.86rem;
    line-height: 1.45;
    color: #64748b;
    max-width: 52ch;
}
.rot-banner__sub strong {
    color: #475569;
    font-weight: 700;
}

.rot-legend {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    border-radius: 12px;
    background: #ffffff;
    border: 1px solid #eceff4;
}
.rot-legend__item {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 0.74rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    white-space: nowrap;
}
.rot-legend__dot {
    width: 11px;
    height: 11px;
    border-radius: 50%;
}
.rot-legend__dot--from {
    background: #f43f5e;
    box-shadow: 0 0 0 3px rgba(244, 63, 94, 0.15);
}
.rot-legend__dot--to {
    background: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
}
.rot-legend__arrow {
    color: #c3cad6;
    font-weight: 700;
}
.rot-banner__total {
    display: inline-flex;
    align-items: baseline;
    gap: 6px;
    font-size: 1.7rem;
    font-weight: 800;
    color: #4f46e5;
    line-height: 1;
}
.rot-banner__total em {
    font-style: normal;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #94a3b8;
}

/* ── Admin clear-all button ── */
.rot-clear {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 12px;
    border: 0;
    background: linear-gradient(180deg, #f43f5e 0%, #e11d48 100%) !important;
    color: #fff !important;
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.01em;
    cursor: pointer;
    transition: all 0.18s ease;
    box-shadow: 0 6px 16px -6px rgba(225, 29, 72, 0.5);
}
.rot-clear:hover {
    background: linear-gradient(180deg, #e11d48 0%, #be123c 100%) !important;
    transform: translateY(-1px);
    box-shadow: 0 10px 22px -8px rgba(225, 29, 72, 0.6);
}
.rot-clear:active { transform: translateY(0); }
.rot-clear__icon {
    width: 16px;
    height: 16px;
    color: #fff !important;
}

/* ── Card list ── */
.rot-list {
    display: flex;
    flex-direction: column;
    gap: 26px;
}

/* ── Empty ── */
.rot-empty {
    text-align: center;
    padding: 64px 24px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(12px);
    border: 1px solid #e6eaf1;
}
.rot-empty__icon {
    width: 44px;
    height: 44px;
    color: #cbd5e1;
    margin: 0 auto 12px;
}
.rot-empty__text {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: #94a3b8;
}

/* ── Footer / pagination ── */
.rot-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    margin-top: 28px;
    padding-top: 18px;
    border-top: 1px solid #e9edf3;
}
.rot-foot__count {
    font-size: 0.78rem;
    font-weight: 700;
    color: #94a3b8;
}
</style>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowRight, Eye, EyeOff, Lock, Mail } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineProps({
    status: {
        type: String,
    },
});

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <GuestLayout>
        <Head title="Ба система ворид шавед" />

        <div v-if="status" class="login-status">
            {{ status }}
        </div>

        <form class="login-form" @submit.prevent="submit">
            <!-- Email -->
            <div class="field">
                <label for="email" class="field__label">Почтаи электронӣ</label>
                <div class="field__control" :class="{ 'is-error': form.errors.email }">
                    <Mail class="field__icon" />
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="example@tojiktelecom.tj"
                    >
                </div>
                <InputError class="field__error" :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div class="field">
                <label for="password" class="field__label">Парол</label>
                <div class="field__control" :class="{ 'is-error': form.errors.password }">
                    <Lock class="field__icon" />
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    >
                    <button
                        type="button"
                        class="field__toggle"
                        :aria-label="showPassword ? 'Пинҳон кардан' : 'Намоиш додан'"
                        @click="showPassword = !showPassword"
                    >
                        <component :is="showPassword ? EyeOff : Eye" />
                    </button>
                </div>
                <InputError class="field__error" :message="form.errors.password" />
            </div>

            <!-- Remember -->
            <label class="remember">
                <input v-model="form.remember" type="checkbox">
                <span class="remember__box" />
                <span class="remember__text">Маро дар хотир нигоҳ доред</span>
            </label>

            <!-- Submit -->
            <button type="submit" class="login-btn" :class="{ 'is-loading': form.processing }" :disabled="form.processing">
                <span>{{ form.processing ? 'Лутфан интизор шавед…' : 'Ворид шудан' }}</span>
                <ArrowRight class="login-btn__arrow" />
            </button>
        </form>
    </GuestLayout>
</template>

<style scoped>
.login-status {
    margin-bottom: 1.25rem;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    background: #ecfdf5;
    color: #047857;
    font-size: 0.85rem;
    font-weight: 600;
    border: 1px solid #a7f3d0;
}

.login-form { display: grid; gap: 1.25rem; }

.field { display: grid; gap: 0.45rem; }
.field__label {
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    color: #334155;
}
.field__control {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    background: #f4f7fb;
    border: 1.5px solid #e2e8f0;
    border-radius: 13px;
    padding: 0 0.9rem;
    transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
}
.field__control:focus-within {
    border-color: #009cf1;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(0, 156, 241, 0.12);
}
.field__control.is-error {
    border-color: #ef4444;
    background: #fef2f2;
}
.field__icon {
    width: 18px;
    height: 18px;
    color: #94a3b8;
    flex-shrink: 0;
    transition: color 0.2s;
}
.field__control:focus-within .field__icon { color: #009cf1; }
.field__control input {
    flex: 1;
    border: 0;
    outline: none !important;
    -webkit-appearance: none;
    appearance: none;
    box-shadow: none;
    background: transparent;
    padding: 0.8rem 0;
    font-family: inherit;
    font-size: 0.95rem;
    font-weight: 500;
    color: #0f1b2d;
    min-width: 0;
}
.field__control input::placeholder { color: #b6c2d2; font-weight: 500; }
/* Hide the native square reveal/clear buttons Edge & IE paint inside
   password/email inputs (we provide our own show/hide toggle). */
.field__control input::-ms-reveal,
.field__control input::-ms-clear {
    display: none;
    width: 0;
    height: 0;
}
.field__control input::-webkit-credentials-auto-fill-button,
.field__control input::-webkit-contacts-auto-fill-button {
    visibility: hidden;
    display: none !important;
    pointer-events: none;
}
/* Kill the inner border/background Chrome paints on autofilled inputs. */
.field__control input:-webkit-autofill,
.field__control input:-webkit-autofill:hover,
.field__control input:-webkit-autofill:focus {
    -webkit-text-fill-color: #0f1b2d;
    -webkit-box-shadow: 0 0 0 1000px transparent inset;
    box-shadow: 0 0 0 1000px transparent inset;
    transition: background-color 9999s ease-in-out 0s;
}
.field__toggle {
    display: grid;
    place-items: center;
    border: 0;
    background: transparent;
    cursor: pointer;
    color: #94a3b8;
    padding: 0.25rem;
    transition: color 0.15s;
}
.field__toggle:hover { color: #009cf1; }
.field__toggle:focus,
.field__toggle:focus-visible { outline: none; }
.field__toggle svg { width: 18px; height: 18px; }
.field__error { font-size: 0.8rem; }

/* Remember checkbox */
.remember {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    cursor: pointer;
    user-select: none;
    margin-top: -0.25rem;
}
.remember input { position: absolute; opacity: 0; pointer-events: none; }
.remember__box {
    width: 20px;
    height: 20px;
    border-radius: 7px;
    border: 1.5px solid #cbd5e1;
    background: #fff;
    position: relative;
    transition: all 0.18s;
    flex-shrink: 0;
}
.remember input:checked + .remember__box {
    background: #009cf1;
    border-color: #009cf1;
}
.remember input:checked + .remember__box::after {
    content: '';
    position: absolute;
    left: 6.5px;
    top: 3px;
    width: 5px;
    height: 9px;
    border: solid #fff;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}
.remember input:focus-visible + .remember__box {
    box-shadow: 0 0 0 4px rgba(0, 156, 241, 0.18);
}
.remember__text {
    font-size: 0.88rem;
    color: #475569;
    font-weight: 600;
}

/* Submit */
.login-btn {
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.55rem;
    width: 100%;
    border: 0;
    cursor: pointer;
    padding: 0.95rem 1.25rem;
    border-radius: 13px;
    font-family: inherit;
    font-size: 0.98rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #009cf1 0%, #0061b8 100%);
    box-shadow: 0 10px 24px -8px rgba(0, 97, 184, 0.6);
    transition: transform 0.18s, box-shadow 0.18s, filter 0.18s;
}
.login-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 16px 30px -10px rgba(0, 97, 184, 0.7);
    filter: brightness(1.04);
}
.login-btn:active:not(:disabled) { transform: translateY(0); }
.login-btn:disabled { cursor: progress; opacity: 0.85; }
.login-btn__arrow {
    width: 18px;
    height: 18px;
    transition: transform 0.2s;
}
.login-btn:hover:not(:disabled) .login-btn__arrow { transform: translateX(4px); }
.login-btn.is-loading .login-btn__arrow { display: none; }
</style>

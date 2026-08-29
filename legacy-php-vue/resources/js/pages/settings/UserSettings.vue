<template>
    <div class="space-y-6">
        <PageHeader :title="$t('nav.userSettings')" subtitle="Manage your profile, security, and preferences." />

        <TabNav :tabs="tabs" v-model="activeTab" />

        <BaseCard padding="lg">
            <div v-if="activeTab === 'profile'" class="space-y-6">
                <h3 class="text-lg font-semibold text-slate-900">{{ $t('common.profile') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <BaseInput v-model="form.name" :label="$t('common.name')" name="name" />
                    <BaseInput v-model="form.email" :label="$t('common.email')" name="email" type="email" />
                    <BaseInput v-model="form.phone" :label="$t('common.phone')" name="phone" />
                </div>
                <BaseButton @click="saveProfile" :loading="saving">{{ $t('common.save') }}</BaseButton>
            </div>

            <div v-else-if="activeTab === 'password'" class="space-y-6">
                <h3 class="text-lg font-semibold text-slate-900">{{ $t('common.change_password') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-xl">
                    <BaseInput v-model="pwdForm.current" :label="$t('common.current_password')" name="current" type="password" />
                    <BaseInput v-model="pwdForm.new" :label="$t('common.new_password')" name="new" type="password" />
                    <BaseInput v-model="pwdForm.confirm" :label="$t('common.confirm_password')" name="confirm" type="password" />
                </div>
                <BaseButton @click="savePassword" :loading="savingPwd" variant="warning">{{ $t('common.save') }}</BaseButton>
            </div>

            <div v-else-if="activeTab === 'notifications'" class="space-y-6">
                <h3 class="text-lg font-semibold text-slate-900">{{ $t('common.notifications') }}</h3>
                <div class="space-y-4">
                    <label v-for="n in notifications" :key="n.key" class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                        <div>
                            <p class="font-medium text-slate-900">{{ $t(n.label) }}</p>
                            <p class="text-sm text-slate-500">{{ $t(n.desc) }}</p>
                        </div>
                        <input type="checkbox" v-model="n.enabled" class="w-5 h-5 text-brand-600 rounded border-slate-300 focus:ring-brand-500" />
                    </label>
                </div>
            </div>

            <div v-else-if="activeTab === 'appearance'" class="space-y-6">
                <h3 class="text-lg font-semibold text-slate-900">{{ $t('common.appearance') }}</h3>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-3">{{ $t('common.theme') }}</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label v-for="t in themes" :key="t.value" class="relative cursor-pointer">
                            <input type="radio" v-model="theme" :value="t.value" class="sr-only peer" />
                            <div class="p-4 border-2 rounded-lg text-center transition-colors peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:ring-2 peer-checked:ring-brand-200 border-slate-200 hover:border-slate-300">
                                <div class="w-12 h-12 rounded-lg mx-auto mb-2" :class="t.previewClass"></div>
                                <p class="font-medium text-slate-900">{{ $t(t.label) }}</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </BaseCard>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useSettingsStore } from '@/stores/settings';
import { useToastStore } from '@/stores/toast';
import BaseCard from '@/components/ui/BaseCard.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import TabNav from '@/components/ui/TabNav.vue';

const auth = useAuthStore();
const settings = useSettingsStore();
const toast = useToastStore();

const tabs = [
    { key: 'profile', label: 'Profile' },
    { key: 'password', label: 'Security' },
    { key: 'notifications', label: 'Notifications' },
    { key: 'appearance', label: 'Appearance' },
];

const activeTab = ref('profile');

const form = reactive({
    name: '',
    email: '',
    phone: '',
});

const pwdForm = reactive({
    current: '',
    new: '',
    confirm: '',
});

const notifications = reactive([
    { key: 'email', label: 'common.notifications', desc: 'common.notifications', enabled: true },
    { key: 'lowStock', label: 'common.low_stock_alerts', desc: 'common.low_stock_alerts_desc', enabled: true },
    { key: 'sales', label: 'common.daily_sales_summary', desc: 'common.daily_sales_summary_desc', enabled: false },
]);

const themes = [
    { value: 'light', label: 'common.light_mode', previewClass: 'bg-white border border-slate-200' },
    { value: 'dark', label: 'common.dark_mode', previewClass: 'bg-slate-900' },
    { value: 'system', label: 'common.system_default', previewClass: 'bg-gradient-to-r from-white to-slate-900' },
];

const theme = ref('system');

const saving = ref(false);
const savingPwd = ref(false);

onMounted(() => {
    if (auth.user) {
        form.name = auth.user.name || '';
        form.email = auth.user.email || '';
        form.phone = auth.user.phone || '';
    }
});

async function saveProfile() {
    saving.value = true;
    try {
        // TODO: Call API to update profile
        // await axios.put('/api/user/profile', form);
        toast.success('Profile updated successfully');
    } catch (e) {
        toast.error('Failed to update profile');
    } finally {
        saving.value = false;
    }
}

async function savePassword() {
    if (pwdForm.new !== pwdForm.confirm) {
        toast.error('Passwords do not match');
        return;
    }
    savingPwd.value = true;
    try {
        // TODO: Call API to change password
        // await axios.put('/api/user/password', pwdForm);
        toast.success('Password changed successfully');
        pwdForm.current = pwdForm.new = pwdForm.confirm = '';
    } catch (e) {
        toast.error('Failed to change password');
    } finally {
        savingPwd.value = false;
    }
}
</script>

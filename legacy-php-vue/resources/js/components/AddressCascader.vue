<template>
    <div class="space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <BaseSelect
                v-model="local.province_id"
                name="province_id"
                label="Province"
                :options="provinceOptions"
                :required="required.province"
                :error="errors?.province_id"
                placeholder="Select province"
                @update:modelValue="onProvinceChange"
            />
            <BaseSelect
                v-model="local.district_id"
                name="district_id"
                label="District"
                :options="districtOptions"
                :required="required.district"
                :error="errors?.district_id"
                :disabled="!local.province_id || loadingDistricts"
                placeholder="Select district"
                @update:modelValue="onDistrictChange"
            />
            <BaseSelect
                v-model="local.commune_id"
                name="commune_id"
                label="Commune"
                :options="filteredCommunes"
                :required="required.commune"
                :error="errors?.commune_id"
                :disabled="!local.district_id || loadingCommunes"
                placeholder="Select commune"
                @update:modelValue="onCommuneChange"
            />
            <BaseSelect
                v-model="local.village_id"
                name="village_id"
                label="Village"
                :options="filteredVillages"
                :required="required.village"
                :error="errors?.village_id"
                :disabled="!local.commune_id || loadingVillages"
                placeholder="Select village"
            />
        </div>

        <div v-if="communes.length >= 30">
            <BaseInput v-model="communeSearch" label="Search communes" placeholder="Type to filter" />
        </div>
        <div v-if="villages.length >= 30">
            <BaseInput v-model="villageSearch" label="Search villages" placeholder="Type to filter" />
        </div>

        <div v-if="showAddressField">
            <label :for="addressId" class="block text-sm font-medium text-slate-700 mb-1">Street / House no.</label>
            <textarea
                :id="addressId"
                v-model="local.address"
                name="address"
                rows="2"
                class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:outline-none focus:ring-2 focus:border-brand-500 focus:ring-brand-500"
                :placeholder="addressPlaceholder"
            />
            <p v-if="errors?.address" class="mt-1 text-xs text-rose-600">{{ errors.address }}</p>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, computed, watch, onMounted } from 'vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import { useAddressesStore } from '@/stores/addresses';

const props = defineProps({
    modelValue: {
        type: Object,
        required: true,
        default: () => ({
            province_id: null,
            district_id: null,
            commune_id: null,
            village_id: null,
            address: '',
        }),
    },
    required: {
        type: Object,
        default: () => ({ province: true, district: true, commune: false, village: false }),
    },
    errors: { type: Object, default: () => ({}) },
    showAddressField: { type: Boolean, default: true },
    addressPlaceholder: { type: String, default: 'Street, house number, landmark' },
});

const emit = defineEmits(['update:modelValue']);

const store = useAddressesStore();
const addressId = `addr-${Math.random().toString(36).slice(2, 9)}`;

const local = reactive({
    province_id: props.modelValue.province_id ?? null,
    district_id: props.modelValue.district_id ?? null,
    commune_id: props.modelValue.commune_id ?? null,
    village_id: props.modelValue.village_id ?? null,
    address: props.modelValue.address ?? '',
});

const provinceOptions = computed(() =>
    store.provinces.map((p) => ({ value: p.value, label: p.label })),
);

const districts = ref([]);
const communes = ref([]);
const villages = ref([]);

const loadingDistricts = ref(false);
const loadingCommunes = ref(false);
const loadingVillages = ref(false);

const communeSearch = ref('');
const villageSearch = ref('');

const districtOptions = computed(() =>
    districts.value.map((d) => ({ value: d.value, label: d.label })),
);
const filteredCommunes = computed(() => {
    const s = communeSearch.value.toLowerCase();
    return communes.value
        .filter(
            (c) =>
                !s ||
                c.label.toLowerCase().includes(s) ||
                (c.label_km || '').includes(communeSearch.value),
        )
        .map((c) => ({ value: c.value, label: c.label }));
});
const filteredVillages = computed(() => {
    const s = villageSearch.value.toLowerCase();
    return villages.value
        .filter(
            (v) =>
                !s ||
                v.label.toLowerCase().includes(s) ||
                (v.label_km || '').includes(villageSearch.value),
        )
        .map((v) => ({ value: v.value, label: v.label }));
});

async function loadForCurrentState() {
    if (local.province_id) {
        districts.value = await store.loadDistricts(local.province_id);
        if (local.district_id) {
            communes.value = await store.loadCommunes(local.district_id);
            if (local.commune_id) {
                villages.value = await store.loadVillages(local.commune_id);
            } else {
                villages.value = [];
            }
        } else {
            communes.value = [];
            villages.value = [];
        }
    } else {
        districts.value = [];
        communes.value = [];
        villages.value = [];
    }
}

async function onProvinceChange(value) {
    local.district_id = null;
    local.commune_id = null;
    local.village_id = null;
    communes.value = [];
    villages.value = [];
    if (!value) {
        districts.value = [];
        emitUpdate();
        return;
    }
    loadingDistricts.value = true;
    try {
        districts.value = await store.loadDistricts(value);
    } finally {
        loadingDistricts.value = false;
    }
    emitUpdate();
}

async function onDistrictChange(value) {
    local.commune_id = null;
    local.village_id = null;
    villages.value = [];
    if (!value) {
        communes.value = [];
        emitUpdate();
        return;
    }
    loadingCommunes.value = true;
    try {
        communes.value = await store.loadCommunes(value);
    } finally {
        loadingCommunes.value = false;
    }
    emitUpdate();
}

async function onCommuneChange(value) {
    local.village_id = null;
    if (!value) {
        villages.value = [];
        emitUpdate();
        return;
    }
    loadingVillages.value = true;
    try {
        villages.value = await store.loadVillages(value);
    } finally {
        loadingVillages.value = false;
    }
    emitUpdate();
}

watch(() => local.address, emitUpdate);
watch(() => local.village_id, emitUpdate);

function emitUpdate() {
    emit('update:modelValue', { ...local });
}

watch(
    () => props.modelValue,
    (nv) => {
        if (!nv) return;
        if (
            nv.province_id !== local.province_id ||
            nv.district_id !== local.district_id ||
            nv.commune_id !== local.commune_id ||
            nv.village_id !== local.village_id ||
            nv.address !== local.address
        ) {
            local.province_id = nv.province_id ?? null;
            local.district_id = nv.district_id ?? null;
            local.commune_id = nv.commune_id ?? null;
            local.village_id = nv.village_id ?? null;
            local.address = nv.address ?? '';
            loadForCurrentState();
        }
    },
    { deep: true },
);

onMounted(async () => {
    await store.loadProvinces();
    await loadForCurrentState();
});
</script>

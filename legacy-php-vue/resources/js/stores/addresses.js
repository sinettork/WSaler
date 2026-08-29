import { defineStore } from 'pinia';
import axios from 'axios';
import { ref } from 'vue';

export const useAddressesStore = defineStore('addresses', () => {
    const provinces = ref([]);
    const provincesLoaded = ref(false);
    const childCache = ref({}); // key -> [{value, code, label, label_km}]

    async function loadProvinces(force = false) {
        if (provincesLoaded.value && !force) return provinces.value;
        const { data } = await axios.get('/api/addresses/provinces');
        provinces.value = data.data;
        provincesLoaded.value = true;
        return provinces.value;
    }

    async function loadDistricts(provinceId, search = '') {
        return loadChildren(
            `provinces:${provinceId}`,
            `/api/addresses/provinces/${provinceId}/districts`,
            search,
        );
    }

    async function loadCommunes(districtId, search = '') {
        return loadChildren(
            `districts:${districtId}`,
            `/api/addresses/districts/${districtId}/communes`,
            search,
        );
    }

    async function loadVillages(communeId, search = '') {
        return loadChildren(
            `communes:${communeId}`,
            `/api/addresses/communes/${communeId}/villages`,
            search,
        );
    }

    async function loadChildren(key, url, search) {
        if (!childCache.value[key]) {
            const { data } = await axios.get(url);
            childCache.value[key] = data.data;
        }
        if (!search) return childCache.value[key];
        const s = search.toLowerCase();
        return childCache.value[key].filter(
            (item) =>
                item.label.toLowerCase().includes(s) ||
                (item.label_km || '').includes(search),
        );
    }

    function resetChildren(...keys) {
        for (const k of keys) delete childCache.value[k];
    }

    return {
        provinces,
        childCache,
        loadProvinces,
        loadDistricts,
        loadCommunes,
        loadVillages,
        resetChildren,
    };
});

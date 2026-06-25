<script setup>
const props = defineProps({
    columns: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    rowKey: { type: String, default: 'id' },
    loading: { type: Boolean, default: false },
    emptyText: { type: String, default: 'No data available' },
});

function alignClass(align) {
    if (align === 'right') return 'text-right';
    if (align === 'center') return 'text-center';
    return 'text-left';
}

function cellStyle(col) {
    if (!col.width) return undefined;
    return { width: col.width, minWidth: col.width };
}

const skeletonRows = 5;
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            scope="col"
                            :class="[
                                'px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500',
                                alignClass(col.align),
                            ]"
                            :style="cellStyle(col)"
                        >
                            {{ col.label }}
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 bg-white">
                    <template v-if="loading">
                        <tr v-for="r in skeletonRows" :key="`sk-${r}`">
                            <td
                                v-for="col in columns"
                                :key="`sk-${r}-${col.key}`"
                                :class="['px-4 py-3', alignClass(col.align)]"
                            >
                                <div class="h-4 bg-slate-200 rounded animate-pulse"></div>
                            </td>
                        </tr>
                    </template>

                    <template v-else-if="rows.length === 0">
                        <tr>
                            <td
                                :colspan="columns.length"
                                class="px-4 py-12 text-center text-sm text-slate-500"
                            >
                                <slot name="empty">{{ emptyText }}</slot>
                            </td>
                        </tr>
                    </template>

                    <template v-else>
                        <tr
                            v-for="row in rows"
                            :key="row[rowKey]"
                            class="hover:bg-slate-50 transition-colors"
                        >
                            <td
                                v-for="col in columns"
                                :key="`${row[rowKey]}-${col.key}`"
                                :class="['px-4 py-3 text-sm text-slate-700 whitespace-nowrap', alignClass(col.align)]"
                            >
                                {{ row[col.key] }}
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</template>

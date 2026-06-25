import { reactive, computed, ref } from 'vue';

/**
 * usePosCart
 *
 * Reactive state and helpers for the POS screen. Owns the active cart,
 * customer, warehouse, payments, and totals. Build the API payload via
 * `buildPayload()` and pass it to salesStore.create().
 *
 * Cart line shape:
 *   {
 *     id: string,                  // local uuid (for list keys)
 *     product_id: number,
 *     product_name: string,
 *     product_sku: string,
 *     variation_id: number | null,
 *     unit_id: number,
 *     unit_name: string,
 *     quantity: number,
 *     unit_price: number,
 *     discount: number,
 *     stock: number,                // available stock for validation
 *   }
 */
export function usePosCart() {
    const lines = ref([]);
    const customer = ref(null);
    const warehouse = ref(null);
    const payments = ref([]);
    const discount = ref(0);
    const tax = ref(0);
    const notes = ref('');

    const subtotal = computed(() =>
        round2(
            lines.value.reduce((sum, l) => sum + lineTotal(l), 0)
        )
    );

    const total = computed(() =>
        round2(subtotal.value - Number(discount.value || 0) + Number(tax.value || 0))
    );

    const paid = computed(() =>
        round2(payments.value.reduce((sum, p) => sum + Number(p.amount || 0), 0))
    );

    // Positive = customer owes more, negative = change due
    const balance = computed(() => round2(total.value - paid.value));

    const changeDue = computed(() => (balance.value < 0 ? Math.abs(balance.value) : 0));

    const remainingToPay = computed(() => (balance.value > 0 ? balance.value : 0));

    const itemCount = computed(() =>
        lines.value.reduce((sum, l) => sum + Number(l.quantity || 0), 0)
    );

    const isEmpty = computed(() => lines.value.length === 0);

    /* ---------------- mutations ---------------- */

    function addLine(product, unit, quantity = 1, unitPrice = null) {
        const variationId = product.variation_id ?? null;
        const unitId = unit?.id ?? product.unit_id ?? null;
        const unitName = unit?.name ?? product.unit_name ?? '';
        const price = unitPrice ?? pickDefaultPrice(product);

        // If the same product + variation + unit is already in the cart, increment qty
        const existing = lines.value.find(
            (l) => l.product_id === product.id && l.variation_id === variationId && l.unit_id === unitId
        );
        if (existing) {
            existing.quantity = Number(existing.quantity) + Number(quantity);
            existing.line_total = lineTotal(existing);
            return existing;
        }

        const line = {
            id: crypto.randomUUID ? crypto.randomUUID() : String(Date.now() + Math.random()),
            product_id: product.id,
            product_name: product.name,
            product_sku: product.sku ?? '',
            variation_id: variationId,
            unit_id: unitId,
            unit_name: unitName,
            quantity: Number(quantity),
            unit_price: round2(price),
            discount: 0,
            stock: product.total_stock ?? product.stock ?? null,
        };
        line.line_total = lineTotal(line);
        lines.value.push(line);
        return line;
    }

    function removeLine(lineId) {
        lines.value = lines.value.filter((l) => l.id !== lineId);
    }

    function updateLineQuantity(lineId, quantity) {
        const l = findLine(lineId);
        if (!l) return;
        l.quantity = Math.max(1, Math.floor(Number(quantity) || 0));
        l.line_total = lineTotal(l);
    }

    function updateLinePrice(lineId, price) {
        const l = findLine(lineId);
        if (!l) return;
        l.unit_price = round2(Math.max(0, Number(price) || 0));
        l.line_total = lineTotal(l);
    }

    function updateLineDiscount(lineId, discount) {
        const l = findLine(lineId);
        if (!l) return;
        l.discount = round2(Math.max(0, Number(discount) || 0));
        l.line_total = lineTotal(l);
    }

    function setCustomer(c) {
        customer.value = c ? {
            id: c.id,
            name: c.name,
            code: c.code,
            credit_limit: Number(c.credit_limit ?? 0),
            current_balance: Number(c.current_balance ?? 0),
            type: c.type,
        } : null;
    }

    function setWarehouse(w) {
        warehouse.value = w ? { id: w.id, name: w.name, code: w.code } : null;
    }

    function addPayment(method, amount, reference = '') {
        payments.value.push({
            method,
            amount: round2(Number(amount) || 0),
            reference: reference || null,
        });
    }

    function setPayments(list) {
        payments.value = list.map((p) => ({
            method: p.method,
            amount: round2(Number(p.amount) || 0),
            reference: p.reference || null,
        }));
    }

    function removePayment(idx) {
        payments.value.splice(idx, 1);
    }

    function setDiscount(value) {
        discount.value = round2(Math.max(0, Number(value) || 0));
    }

    function setTax(value) {
        tax.value = round2(Math.max(0, Number(value) || 0));
    }

    function clear() {
        lines.value = [];
        customer.value = null;
        payments.value = [];
        discount.value = 0;
        tax.value = 0;
        notes.value = '';
        // warehouse is intentionally retained — usually stays the same shift
    }

    /* ---------------- helpers ---------------- */

    function findLine(lineId) {
        return lines.value.find((l) => l.id === lineId);
    }

    function lineTotal(line) {
        return round2(Number(line.quantity) * Number(line.unit_price) - Number(line.discount || 0));
    }

    function pickDefaultPrice(product) {
        // Try common wholesale price fields in priority order
        const candidates = [
            product.wholesale_price,
            product.retail_price,
            product.distributor_price,
            product.price,
        ];
        for (const c of candidates) {
            const n = Number(c);
            if (Number.isFinite(n) && n > 0) return n;
        }
        return 0;
    }

    function round2(n) {
        return Math.round(Number(n) * 100) / 100;
    }

    /**
     * Build the payload expected by POST /api/sales.
     */
    function buildPayload() {
        return {
            customer_id: customer.value?.id ?? null,
            warehouse_id: warehouse.value?.id,
            discount: Number(discount.value) || 0,
            tax: Number(tax.value) || 0,
            notes: notes.value || null,
            items: lines.value.map((l) => ({
                product_id: l.product_id,
                variation_id: l.variation_id ?? null,
                unit_id: l.unit_id,
                quantity: Number(l.quantity),
                unit_price: Number(l.unit_price),
                discount: Number(l.discount || 0),
            })),
            payments: payments.value.map((p) => ({
                method: p.method,
                amount: Number(p.amount),
                reference: p.reference || null,
            })),
        };
    }

    return {
        // state
        lines,
        customer,
        warehouse,
        payments,
        discount,
        tax,
        notes,

        // computed
        subtotal,
        total,
        paid,
        balance,
        changeDue,
        remainingToPay,
        itemCount,
        isEmpty,

        // mutations
        addLine,
        removeLine,
        updateLineQuantity,
        updateLinePrice,
        updateLineDiscount,
        setCustomer,
        setWarehouse,
        addPayment,
        setPayments,
        removePayment,
        setDiscount,
        setTax,
        clear,

        // helpers
        buildPayload,
        lineTotal,
    };
}

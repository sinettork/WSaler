// Pure currency formatting helpers.
// NO imports — this file must remain importable by Node directly (for tests).
// All Vue/Pinia-aware wrappers live in useCurrency.js.

export function convert(amountUsd, settings = {}) {
    const n = Number(amountUsd) || 0;
    return settings.displayCurrency === 'KHR'
        ? Math.round(n * (settings.exchangeRate || 0))
        : n;
}

export function formatNumber(value, opts = {}, settings = {}) {
    const n = Number(value) || 0;
    const d = opts.decimals ?? (settings.displayCurrency === 'KHR' ? 0 : 2);
    const fixed = n.toFixed(d);
    const [int, frac] = fixed.split('.');
    const thousands = settings.locale === 'km' ? '.' : ',';
    const decimal = settings.locale === 'km' ? ',' : '.';
    const intGrouped = int.replace(/\B(?=(\d{3})+(?!\d))/g, thousands);
    const formatted = frac !== undefined
        ? intGrouped + decimal + frac
        : intGrouped;
    return { formatted, intPart: intGrouped, fracPart: frac ?? '' };
}

export function formatMoneyPure(amountUsd, opts = {}, settings = {}) {
    const n = convert(amountUsd, settings);
    const isNegative = n < 0;
    const abs = isNegative ? -n : n;
    const { formatted } = formatNumber(abs, opts, settings);
    const symbol = settings.displayCurrency === 'KHR' ? '៛' : '$';
    const position = settings.displayCurrency === 'KHR' ? 'suffix' : 'prefix';
    const body = position === 'suffix' ? `${formatted}${symbol}` : `${symbol}${formatted}`;
    return isNegative ? `-${body}` : body;
}

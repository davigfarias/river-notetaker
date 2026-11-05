/**
 * Roving-tabindex target for a key press on a tablist.
 * Returns the new index, or null when the key is not a navigation key.
 *
 * @param {{count: number, current: number, key: string, disabled?: number[]}} state
 * @returns {number|null}
 */
export function nextTabIndex({ count, current, key, disabled = [] }) {
    const step = { ArrowRight: 1, ArrowLeft: -1 }[key];
    const enabled = [...Array(count).keys()].filter((index) => !disabled.includes(index));

    if (enabled.length === 0) {
        return null;
    }

    if (key === 'Home') {
        return enabled[0];
    }

    if (key === 'End') {
        return enabled[enabled.length - 1];
    }

    if (step === undefined) {
        return null;
    }

    const position = enabled.indexOf(current);
    const base = position === -1 ? (step === 1 ? -1 : 0) : position;

    return enabled[(base + step + enabled.length) % enabled.length];
}

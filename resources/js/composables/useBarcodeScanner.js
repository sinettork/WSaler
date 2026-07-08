import { ref, onMounted, onUnmounted } from 'vue';

/**
 * Barcode scanner composable for "keyboard wedge" USB barcode scanners.
 *
 * Barcode scanners send rapid keystrokes ending with Enter (or configurable
 * suffix). This composable listens for rapid key sequences (typically a
 * barcode) and emits the detected code.
 *
 * @param {object} options
 * @param {number} options.threshold  Typing speed threshold in ms between chars to qualify as a barcode (default: 50)
 * @param {number} options.minLength  Minimum characters to qualify as a barcode (default: 5)
 * @param {string} options.suffix     Suffix character that ends a barcode scan (default: 'Enter')
 *
 * @returns {{ code: Ref<string>, onScan: Function, offScan: Function, lastScanned: Ref<string> }}
 */
export function useBarcodeScanner(options = {}) {
  const { threshold = 50, minLength = 5, suffix = 'Enter' } = options;

  const lastScanned = ref('');
  const code = ref('');
  const callbacks = new Set();
  let buffer = '';
  let lastKeyTime = 0;
  let isListening = false;

  function onKeydown(event) {
    const now = Date.now();
    const elapsed = now - lastKeyTime;
    lastKeyTime = now;

    // If slow typing, reset buffer (user is manually typing)
    if (elapsed > threshold) {
      buffer = '';
    }

    // Ignore non-printable special keys except Enter
    if (event.key.length > 1 && event.key !== suffix) {
      // However, if we accumulated a barcode-like string already, don't clear it
      // unless it's a meta key that would interfere with normal use
      if (['Tab', 'Escape', 'Backspace', 'Delete'].includes(event.key)) {
        buffer = '';
      }
      return;
    }

    // Prevent Enter from propagating if we detect a barcode
    if (event.key === suffix) {
      if (buffer.length >= minLength) {
        event.preventDefault();
        event.stopPropagation();

        const detected = buffer;
        lastScanned.value = detected;
        code.value = detected;

        // Fire all registered callbacks
        callbacks.forEach((cb) => cb(detected));

        // Reset
        buffer = '';
        return;
      }
      // Not a barcode (too short) — clear and let Enter pass through
      buffer = '';
      return;
    }

    // Ignore control characters and modifier key shortcuts
    if (event.ctrlKey || event.altKey || event.metaKey) {
      buffer = '';
      return;
    }

    // Ignore if the active element is an input/textarea/select (unless it's the POS search input which we want to intercept)
    // We still collect the barcode even from inside an input — the keydown event
    // is captured globally, and our Enter prevention will stop form submissions.

    buffer += event.key;
  }

  function onScan(callback) {
    callbacks.add(callback);
    if (!isListening) {
      window.addEventListener('keydown', onKeydown, { capture: true });
      isListening = true;
    }
    return () => offScan(callback);
  }

  function offScan(callback) {
    callbacks.delete(callback);
    if (callbacks.size === 0 && isListening) {
      window.removeEventListener('keydown', onKeydown, { capture: true });
      isListening = false;
    }
  }

  // Auto-start listening if a callback is added, auto-stop when all removed
  // This composable doesn't auto-listen — the consumer calls onScan()

  return {
    code,
    lastScanned,
    onScan,
    offScan,
  };
}

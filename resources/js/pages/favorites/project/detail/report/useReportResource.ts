import { onScopeDispose, shallowRef, watch } from 'vue';

interface ReportResourceOptions {
    debounce?: number;
}

/**
 * Mengelola satu request laporan yang sumbernya berupa URL reaktif.
 *
 * Saat URL berubah, request yang sedang berjalan langsung dibatalkan dan ditandai
 * kedaluwarsa — sebelum debounce mulai berhitung — supaya respons lama tidak pernah
 * menyentuh data, error, maupun loading milik request terbaru.
 */
export function useReportResource<T>(url: () => string, fallback: T, options: ReportResourceOptions = {}) {
    const debounceMs = options.debounce ?? 500;

    const data = shallowRef<T>(fallback);
    const loading = shallowRef(false);
    const error = shallowRef<string | null>(null);

    let latestRequestId = 0;
    let controller: AbortController | null = null;
    let timer: ReturnType<typeof setTimeout> | null = null;

    const cancelPending = () => {
        if (timer !== null) {
            clearTimeout(timer);
            timer = null;
        }

        controller?.abort();
        controller = null;
    };

    const execute = async (requestId: number, target: string) => {
        const ownController = new AbortController();
        controller = ownController;

        try {
            const response = await fetch(target, {
                headers: { Accept: 'application/json' },
                signal: ownController.signal,
            });

            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}.`);
            }

            const body = await response.json();

            if (requestId !== latestRequestId) {
                return;
            }

            data.value = (body.data ?? fallback) as T;
        } catch (cause) {
            if (requestId !== latestRequestId || ownController.signal.aborted) {
                return;
            }

            error.value = cause instanceof Error ? cause.message : 'Something went wrong while loading the data.';
        } finally {
            if (requestId === latestRequestId) {
                loading.value = false;
            }
        }
    };

    const start = (immediate: boolean) => {
        const requestId = ++latestRequestId;

        cancelPending();

        loading.value = true;
        error.value = null;
        data.value = fallback;

        const target = url();

        if (immediate) {
            void execute(requestId, target);

            return;
        }

        timer = setTimeout(() => {
            timer = null;
            void execute(requestId, target);
        }, debounceMs);
    };

    watch(url, () => start(false), { flush: 'sync' });

    onScopeDispose(() => {
        latestRequestId += 1;
        cancelPending();
    });

    start(true);

    return { data, loading, error };
}

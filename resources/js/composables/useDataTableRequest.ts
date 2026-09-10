import { router, usePage } from '@inertiajs/vue3';
import { dataTableUrl } from '@/lib/dataTableRequest';
import type {
    DataTableFilters,
    DataTableRequestHandler,
    DataTableState,
} from '@/types/data-table';

// Inertia visits share the page URL. Serialize table visits and build each URL
// when it starts, so two tables cannot cancel or overwrite one another.
let pending: Promise<void> = Promise.resolve();

export function useDataTableRequest() {
    const page = usePage();

    return (
        state: DataTableState & { page: number },
        options: {
            url?: string;
            namespace: string;
            propName: string;
            defaults: DataTableState & { page: number };
            reloadProps?: string[];
            requestData?: DataTableFilters | (() => DataTableFilters);
            request?: DataTableRequestHandler;
        },
    ): Promise<void> => {
        const sourcePath = page.url.split('?')[0];
        const sourceComponent = page.component;
        const run = async () => {
            if (
                page.url.split('?')[0] !== sourcePath ||
                page.component !== sourceComponent
            ) {
                return;
            }

            const context = {
                state,
                url: dataTableUrl(
                    page.url,
                    options.url,
                    options.namespace,
                    state,
                    options.defaults,
                    typeof options.requestData === 'function'
                        ? options.requestData()
                        : options.requestData,
                ),
                only: [
                    ...new Set([
                        options.propName,
                        'auth',
                        ...(options.reloadProps ?? []),
                    ]),
                ],
            };

            if (options.request) {
                await options.request(context);

                return;
            }

            await new Promise<void>((resolve, reject) => {
                router.get(
                    context.url,
                    {},
                    {
                        only: context.only,
                        preserveState: true,
                        preserveScroll: true,
                        onError: (errors) =>
                            reject(new Error(Object.values(errors).join(' '))),
                        onFinish: () => resolve(),
                    },
                );
            });
        };

        if (options.request) {
            return run();
        }

        const result = pending.then(run);
        pending = result.catch(() => {});

        return result;
    };
}

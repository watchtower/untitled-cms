import { AxiosInstance } from 'axios';
import { route as ziggyRoute } from 'ziggy-js';
import { PageProps as AppPageProps } from './';

declare global {
    interface Window {
        axios: AxiosInstance;
    }

    /* eslint-disable no-var */
    var route: typeof ziggyRoute;

    /** App version from package.json, injected by Vite `define`. */
    const __APP_VERSION__: string;
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: AppPageProps;
    }
}

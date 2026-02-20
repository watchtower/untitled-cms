export interface Role {
    id: string;
    name: string;
    slug: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    is_active: boolean;
    deleted_at?: string;
    roles?: Role[];
    permissions: string[];
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    appName: string;
    auth: {
        user: User;
        permissions: string[];
    };
    tinymce_api_key?: string;
    canCreate?: boolean;
    canEdit?: boolean;
    canDelete?: boolean;
};

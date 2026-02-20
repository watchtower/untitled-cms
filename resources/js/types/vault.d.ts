export interface VaultFile {
    id: string;
    uuid: string;
    folder_id: string | null;
    original_name: string;
    mime_type: string;
    extension: string;
    size_bytes: number;
    url: string;
    created_at: string;
    updated_at: string;
    width?: number;
    height?: number;
    alt_text?: string;
}

export interface VaultFolder {
    id: string;
    uuid: string;
    parent_id: string | null;
    name: string;
    path_slug?: string;
    children?: VaultFolder[];
    files?: VaultFile[];
    created_at: string;
    updated_at: string;
}

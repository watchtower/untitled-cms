import { useCallback } from 'react';

interface VaultFile {
    uuid: string;
    url: string;
    original_name: string;
    mime_type: string;
}

interface VaultPickerOptions {
    mode?: 'single' | 'multiple';
    type?: 'image' | 'document' | 'all';
    onSelect?: (files: VaultFile[]) => void;
}

/**
 * Hook to open the global VaultPicker from anywhere in the admin.
 * Uses CustomEvents to communicate with the AuthenticatedLayout.
 */
export function useVaultPicker() {
    const openPicker = useCallback((options: VaultPickerOptions) => {
        const event = new CustomEvent('open-vault-picker', {
            detail: options
        });
        window.dispatchEvent(event);
    }, []);

    const openImagePicker = useCallback((onSelect: (file: VaultFile) => void) => {
        openPicker({
            mode: 'single',
            type: 'image',
            onSelect: (files) => {
                if (files.length > 0) {
                    onSelect(files[0]);
                }
            }
        });
    }, [openPicker]);

    return {
        openPicker,
        openImagePicker,
    };
}

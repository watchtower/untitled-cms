import { useCallback } from 'react';
import { useVaultPicker } from './use-vault-picker';

// Fix #1: narrow the interface so setData is required, making direct mutation impossible.
// Callers must pass an actual Inertia useForm() result — the return type of useForm()
// satisfies this interface out of the box.
interface InertiaFormLike {
    [key: string]: any;
    setData(field: string, value: any): void;
}

interface UseVaultImagePickerOptions {
    type?: 'image' | 'document' | 'all';
}

/**
 * Thin wrapper around useVaultPicker that binds a single file selection
 * directly to an Inertia useForm field via setData().
 *
 * Usage:
 *   const { pick, clear, url } = useVaultImagePicker(form, 'featured_image');
 *
 *   <Button onClick={pick}>Choose Image</Button>
 *   {url && <img src={url} />}
 *   {url && <Button onClick={clear}>Remove</Button>}
 */
export function useVaultImagePicker(
    form: InertiaFormLike,
    field: string,
    options: UseVaultImagePickerOptions = {}
) {
    const { openPicker } = useVaultPicker();

    const pick = useCallback(() => {
        openPicker({
            mode: 'single',
            type: options.type ?? 'image',
            onSelect: (files) => {
                if (files.length > 0) {
                    // Use setData() so Inertia updates React state and triggers a re-render.
                    // Direct assignment (form[field] = value) bypasses React state.
                    form.setData(field, files[0].url);
                }
            },
        });
    }, [openPicker, form, field, options.type]);

    const clear = useCallback(() => {
        form.setData(field, null);
    }, [form, field]);

    return {
        pick,
        clear,
        url: form[field] as string | null,
    };
}

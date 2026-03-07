import { type ClassValue, clsx } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs))
}

/**
 * Check if a URL is external to the current application
 */
export function isExternal(url: string | undefined): boolean {
    if (!url) return false;
    try {
        if (url.startsWith('/') || url.startsWith('#')) return false;
        const link = new URL(url);
        return link.origin !== window.location.origin;
    } catch {
        // Fallback for relative paths that URL might fail on
        return false;
    }
}

import { createContext, useContext, useEffect, useState } from "react"

type Theme = "dark" | "light"

type ThemeProviderProps = {
    children: React.ReactNode
    defaultTheme?: Theme
    storageKey?: string
}

type ThemeProviderState = {
    theme: Theme
    setTheme: (theme: Theme) => void
}

const initialState: ThemeProviderState = {
    theme: "light",
    setTheme: () => null,
}

const ThemeProviderContext = createContext<ThemeProviderState>(initialState)

export function ThemeProvider({
    children,
    defaultTheme = "light",
    storageKey = "vite-ui-theme",
}: ThemeProviderProps) {
    const [theme, setTheme] = useState<Theme>(
        () => (localStorage.getItem(storageKey) as Theme) || defaultTheme
    )

    useEffect(() => {
        const root = window.document.documentElement

        root.classList.remove("light", "dark")
        root.classList.add(theme)
    }, [theme])

    useEffect(() => {
        const handleDoubleClick = (e: MouseEvent) => {
            const target = e.target as HTMLElement;

            // Block if clicking on or inside any interactive/component element
            const interactiveSelectors = [
                'button', 'a', 'input', 'select', 'textarea',
                'table', 'form', 'nav', 'aside',
                '[role="button"]', '[role="link"]', '[role="menu"]',
                'svg', 'img',
                '[data-radix-popper-content-wrapper]', // Shadcn components
                '[data-state]', // Interactive shadcn elements
            ].join(', ');

            if (target.closest(interactiveSelectors)) {
                return;
            }

            // Also block if the target itself has many children (likely a component container)
            // Allow BODY and MAIN to have children
            if (target.children.length > 2 && target.tagName !== 'BODY' && target.tagName !== 'MAIN') {
                return;
            }

            // Only allow specific whitespace tags
            const allowedTags = ['BODY', 'MAIN', 'DIV', 'SECTION', 'ARTICLE', 'HEADER'];
            if (!allowedTags.includes(target.tagName)) {
                return;
            }

            // Cycle theme logic — persist to localStorage so it survives refresh
            setTheme(prev => {
                const next: Theme = prev === 'light' ? 'dark' : 'light';
                localStorage.setItem(storageKey, next);
                return next;
            });
        };

        document.addEventListener('dblclick', handleDoubleClick);
        return () => document.removeEventListener('dblclick', handleDoubleClick);
    }, [setTheme]);

    const value = {
        theme,
        setTheme: (theme: Theme) => {
            localStorage.setItem(storageKey, theme)
            setTheme(theme)
        },
    }

    return (
        <ThemeProviderContext.Provider value={value}>
            {children}
        </ThemeProviderContext.Provider>
    )
}

export const useTheme = () => {
    const context = useContext(ThemeProviderContext)

    if (context === undefined)
        throw new Error("useTheme must be used within a ThemeProvider")

    return context
}

import { usePage } from '@inertiajs/react';
import { Button } from "@/Components/ui/button";
import type { PageProps } from '@/types';

type Provider = {
    key: string;
    label: string;
    settingKey: string;
    supported: boolean; // Must match SUPPORTED_PROVIDERS in SocialAuthController
    icon: React.ReactNode;
};

const PROVIDERS: Provider[] = [
    {
        key: 'google',
        label: 'Google',
        settingKey: 'social_login_google_enabled',
        supported: true,
        icon: (
            <svg className="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
                <path
                    d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .533 5.333.533 12S5.867 24 12.48 24c3.44 0 6.04-1.133 8.16-3.293 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.133H12.48z"
                    fill="currentColor"
                />
            </svg>
        ),
    },
    {
        key: 'github',
        label: 'GitHub',
        settingKey: 'social_login_github_enabled',
        supported: true,
        icon: (
            <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12" />
            </svg>
        ),
    },
    {
        key: 'apple',
        label: 'Apple',
        settingKey: 'social_login_apple_enabled',
        supported: false, // Coming soon — no backend driver yet
        icon: (
            <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12.152 6.896c-.948 0-2.415-1.078-3.96-1.04-2.04.027-3.91 1.183-4.961 3.014-2.117 3.675-.546 9.103 1.519 12.09 1.013 1.454 2.208 3.09 3.792 3.039 1.52-.065 2.09-.987 3.935-.987 1.831 0 2.35.987 3.96.948 1.637-.026 2.676-1.48 3.676-2.948 1.156-1.688 1.636-3.325 1.662-3.415-.039-.013-3.182-1.221-3.22-4.857-.026-3.04 2.48-4.494 2.597-4.559-1.429-2.09-3.623-2.324-4.39-2.376-2-.156-3.675 1.09-4.61 1.09zM15.53 3.83c.843-1.012 1.4-2.427 1.245-3.83-1.207.052-2.662.805-3.532 1.818-.78.896-1.454 2.338-1.273 3.714 1.338.104 2.715-.688 3.559-1.701" />
            </svg>
        ),
    },
    {
        key: 'twitter',
        label: 'X',
        settingKey: 'social_login_twitter_enabled',
        supported: false, // Coming soon — no backend driver yet
        icon: (
            <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
            </svg>
        ),
    },
];

export function SocialLoginButtons() {
    const { settings } = usePage<PageProps>().props;

    const enabledProviders = PROVIDERS.filter(
        (p) => p.supported && settings?.[p.settingKey] === true
    );

    if (enabledProviders.length === 0) return null;

    return (
        <div className="flex flex-col gap-4">
            <div className="relative text-center text-sm after:absolute after:inset-0 after:top-1/2 after:z-0 after:flex after:items-center after:border-t after:border-border">
                <span className="relative z-10 bg-background px-2 text-muted-foreground">
                    Or continue with
                </span>
            </div>

            <div className={`grid gap-3 ${enabledProviders.length === 1 ? 'grid-cols-1' : 'grid-cols-2'}`}>
                {enabledProviders.map((provider) => (
                    <Button
                        key={provider.key}
                        variant="outline"
                        type="button"
                        className="w-full"
                        onClick={() => {
                            window.location.href = route('social.redirect', { provider: provider.key });
                        }}
                    >
                        {provider.icon}
                        <span className="ml-2">{provider.label}</span>
                    </Button>
                ))}
            </div>
        </div>
    );
}

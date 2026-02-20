import { PropsWithChildren } from 'react';
import { usePage } from '@inertiajs/react';
import { GalleryVerticalEnd } from "lucide-react"

import { Card, CardContent } from "@/Components/ui/card"
import { ModeToggle } from "@/Components/mode-toggle"

interface AuthLayoutProps {
    title?: string;
    description?: string;
}


export default function AuthLayout({ children, title, description }: PropsWithChildren<AuthLayoutProps>) {
    const { env, settings } = usePage().props as any;
    const bgImage = settings?.auth_image_url || "";
    const appName = env?.APP_NAME || "UntitledCMS";

    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center p-6 md:p-10 overflow-hidden">
            {bgImage && (
                <div className="absolute inset-0 z-0">
                    <img
                        src={bgImage}
                        alt=""
                        className="h-full w-full object-cover blur-sm scale-110 opacity-50 dark:opacity-30"
                    />
                    <div className="absolute inset-0 bg-background/40 backdrop-blur-[2px]" />
                </div>
            )}

            <div className="relative z-10 w-full max-w-sm md:max-w-4xl">
                <Card className="overflow-hidden p-0 shadow-2xl">
                    <CardContent className="grid p-0 md:grid-cols-2">
                        <div className="p-6 md:p-8">
                            <div className="flex justify-between items-center mb-6">
                                <a href="#" className="flex items-center gap-2 font-medium">
                                    <div className="flex h-6 w-6 items-center justify-center rounded-md bg-primary text-primary-foreground">
                                        <GalleryVerticalEnd className="size-4" />
                                    </div>
                                    {appName}
                                </a>
                                <ModeToggle />
                            </div>

                            {children}
                        </div>

                        <div className="bg-muted relative hidden md:block">
                            {bgImage ? (
                                <img
                                    src={bgImage}
                                    alt="Background"
                                    className="absolute inset-0 h-full w-full object-cover"
                                />
                            ) : (
                                <div className="absolute inset-0 bg-neutral-900 flex items-center justify-center text-muted-foreground">
                                    <span className="sr-only">Image Placeholder</span>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
                <div className="text-balance text-center text-xs text-muted-foreground [&_a]:underline [&_a]:underline-offset-4 hover:[&_a]:text-primary mt-4">
                    By clicking continue, you agree to our <a href="#">Terms of Service</a>{" "}
                    and <a href="#">Privacy Policy</a>.
                </div>
            </div>
        </div>
    );
}

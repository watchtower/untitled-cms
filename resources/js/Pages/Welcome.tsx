import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { ModeToggle } from "@/Components/mode-toggle";
import {
    LayoutDashboard,
    ShieldCheck,
    Zap,
    Globe,
    Server,
    Database,
    Layers,
    Code
} from "lucide-react";

export default function Welcome({
    auth,
    laravelVersion,
    phpVersion,
}: PageProps<{ laravelVersion: string; phpVersion: string }>) {
    return (
        <>
            <Head title="Welcome" />
            <div className="min-h-screen bg-background text-foreground flex flex-col">
                {/* Navbar */}
                <header className="sticky top-0 z-40 w-full border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                    <div className="container flex h-16 items-center justify-between px-4 md:px-8">
                        <div className="flex items-center gap-2 font-bold text-xl">
                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                <span className="text-lg">U</span>
                            </div>
                            <span>UntitledCMS</span>
                        </div>
                        <nav className="flex items-center gap-4">
                            {auth.user ? (
                                <Link href={route('dashboard')}>
                                    <Button>Dashboard</Button>
                                </Link>
                            ) : (
                                <>
                                    <Link href={route('login')}>
                                        <Button variant="ghost">Log in</Button>
                                    </Link>
                                    <Link href={route('register')}>
                                        <Button>Get Started</Button>
                                    </Link>
                                </>
                            )}
                            <ModeToggle />
                        </nav>
                    </div>
                </header>

                <main className="flex-1">
                    {/* Hero Section */}
                    <section className="container px-4 md:px-8 py-24 md:py-32 flex flex-col items-center text-center gap-6">
                        <div className="space-y-4 max-w-3xl">
                            <h1 className="text-4xl font-extrabold tracking-tight sm:text-5xl md:text-6xl lg:text-7xl">
                                Modern Admin Panel for <span className="text-primary">Next-Gen Apps</span>
                            </h1>
                            <p className="mx-auto max-w-[700px] text-gray-500 md:text-xl dark:text-gray-400">
                                Built with Laravel 12, Inertia.js, React, and Shadcn UI. A powerful foundation for your next big idea.
                            </p>
                        </div>
                        <div className="flex gap-4">
                            {auth.user ? (
                                <Link href={route('dashboard')}>
                                    <Button size="lg" className="h-12 px-8 text-base">
                                        Go to Dashboard <LayoutDashboard className="ml-2 h-4 w-4" />
                                    </Button>
                                </Link>
                            ) : (
                                <Link href={route('register')}>
                                    <Button size="lg" className="h-12 px-8 text-base">
                                        Start Building <Zap className="ml-2 h-4 w-4" />
                                    </Button>
                                </Link>
                            )}
                            <a href="https://laravel.com/docs" target="_blank" rel="noreferrer">
                                <Button size="lg" variant="outline" className="h-12 px-8 text-base">
                                    Documentation
                                </Button>
                            </a>
                        </div>
                    </section>

                    {/* Features Grid */}
                    <section className="container px-4 md:px-8 py-12 md:py-24 space-y-12">
                        <div className="text-center space-y-2">
                            <h2 className="text-3xl font-bold tracking-tight">Tech Stack & Features</h2>
                            <p className="text-muted-foreground">Everything you need to ship faster.</p>
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                            <Card className="bg-card">
                                <CardHeader>
                                    <Zap className="h-10 w-10 text-primary mb-2" />
                                    <CardTitle>Inertia.js & React</CardTitle>
                                    <CardDescription>
                                        Seamless single-page app experience with server-side routing.
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                            <Card className="bg-card">
                                <CardHeader>
                                    <ShieldCheck className="h-10 w-10 text-primary mb-2" />
                                    <CardTitle>Authentication</CardTitle>
                                    <CardDescription>
                                        Secure login, registration, and password reset flows out of the box.
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                            <Card className="bg-card">
                                <CardHeader>
                                    <Layers className="h-10 w-10 text-primary mb-2" />
                                    <CardTitle>Shadcn UI</CardTitle>
                                    <CardDescription>
                                        Beautifully designed, accessible components built with Radix UI.
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                            <Card className="bg-card">
                                <CardHeader>
                                    <Database className="h-10 w-10 text-primary mb-2" />
                                    <CardTitle>Laravel 12</CardTitle>
                                    <CardDescription>
                                        The latest and greatest PHP framework backend.
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                            <Card className="bg-card">
                                <CardHeader>
                                    <Globe className="h-10 w-10 text-primary mb-2" />
                                    <CardTitle>Global Context</CardTitle>
                                    <CardDescription>
                                        Manage themes and UI state globally with React Context.
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                            <Card className="bg-card">
                                <CardHeader>
                                    <Code className="h-10 w-10 text-primary mb-2" />
                                    <CardTitle>TypeScript</CardTitle>
                                    <CardDescription>
                                        Fully typed codebase for better developer experience and safety.
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                            <Card className="bg-card">
                                <CardHeader>
                                    <LayoutDashboard className="h-10 w-10 text-primary mb-2" />
                                    <CardTitle>Admin Dashboard</CardTitle>
                                    <CardDescription>
                                        Pre-built dashboard layout with sidebar and breadcrumbs.
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                            <Card className="bg-card">
                                <CardHeader>
                                    <Server className="h-10 w-10 text-primary mb-2" />
                                    <CardTitle>Robust Backend</CardTitle>
                                    <CardDescription>
                                        Powered by PHP v{phpVersion} and Laravel v{laravelVersion}.
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                        </div>
                    </section>
                </main>

                <footer className="border-t py-6 md:py-0">
                    <div className="container flex flex-col items-center justify-between gap-4 md:h-24 md:flex-row px-4 md:px-8">
                        <p className="text-center text-sm leading-loose text-muted-foreground md:text-left">
                            Built by <a href="#" className="font-medium underline underline-offset-4">UntitledCMS Team</a>.
                            The source code is available on <a href="#" className="font-medium underline underline-offset-4">GitHub</a>.
                        </p>
                        <p className="text-center text-sm text-muted-foreground">
                            Laravel v{laravelVersion} (PHP v{phpVersion})
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}

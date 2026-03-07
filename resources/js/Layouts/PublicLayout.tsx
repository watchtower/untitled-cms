import React, { PropsWithChildren, useState, forwardRef } from 'react';
import { usePage, Link, router } from '@inertiajs/react';
import { cn, isExternal } from '@/lib/utils';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { ModeToggle } from '@/Components/mode-toggle';
import { User, Menu as MenuIcon, LogOut, LayoutDashboard, Settings } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    NavigationMenuTrigger,
    navigationMenuTriggerStyle,
} from '@/Components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from "@/Components/ui/sheet";

export default function PublicLayout({ children }: PropsWithChildren) {
    const { auth, menus, settings } = usePage<any>().props;
    const user = auth.user;
    const primaryNav = menus?.['app_header']?.items || [];
    const footerNav = menus?.['footer-navigation']?.items || [];

    const handleLogout = () => {
        router.post(route('logout'));
    };

    const NavLink = forwardRef<HTMLAnchorElement, { item: any, className?: string, children?: React.ReactNode }>(
        ({ item, className, children }, ref) => {
            if (isExternal(item.url)) {
                return (
                    <a
                        ref={ref}
                        href={item.url}
                        target={item.target}
                        rel={item.target === '_blank' ? 'noopener noreferrer' : undefined}
                        className={className}
                    >
                        {children || item.title}
                    </a>
                );
            }
            return (
                <Link ref={ref as any} href={item.url} target={item.target} className={className}>
                    {children || item.title}
                </Link>
            );
        }
    );
    NavLink.displayName = 'NavLink';

    return (
        <div className="min-h-screen bg-background text-foreground font-sans flex flex-col">
            <header className="bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 sticky top-0 z-50 w-full border-b">
                <div className="container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex h-16 items-center justify-between">
                    <div className="flex gap-6 md:gap-10 items-center">
                        <Link href="/" className="flex items-center space-x-2">
                            <ApplicationLogo className="block h-8 w-auto fill-current" />
                            <span className="font-bold inline-block">{settings?.site_name || 'Untitled CMS'}</span>
                        </Link>

                        {/* Desktop Navigation */}
                        {primaryNav.length > 0 && (
                            <div className="hidden md:flex">
                                <NavigationMenu>
                                    <NavigationMenuList>
                                        {primaryNav.map((item: any) => (
                                            <NavigationMenuItem key={item.id}>
                                                {item.subItems && item.subItems.length > 0 ? (
                                                    <>
                                                        <NavigationMenuTrigger className="bg-transparent">{item.title}</NavigationMenuTrigger>
                                                        <NavigationMenuContent>
                                                            <ul className="grid w-[400px] gap-3 p-4 md:w-[500px] md:grid-cols-2 lg:w-[600px] bg-popover">
                                                                {item.subItems.map((sub: any) => (
                                                                    <li key={sub.id}>
                                                                        <NavigationMenuLink asChild>
                                                                            <NavLink
                                                                                item={sub}
                                                                                className="block select-none space-y-1 rounded-md p-3 leading-none no-underline outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground"
                                                                            >
                                                                                <div className="text-sm font-medium leading-none">{sub.title}</div>
                                                                            </NavLink>
                                                                        </NavigationMenuLink>
                                                                    </li>
                                                                ))}
                                                            </ul>
                                                        </NavigationMenuContent>
                                                    </>
                                                ) : (
                                                    <NavLink item={item} className={navigationMenuTriggerStyle() + " bg-transparent"} />
                                                )}
                                            </NavigationMenuItem>
                                        ))}
                                    </NavigationMenuList>
                                </NavigationMenu>
                            </div>
                        )}
                    </div>

                    <div className="flex items-center gap-2 md:gap-4">
                        <ModeToggle />

                        {user ? (
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="ghost" className="relative h-9 w-9 rounded-full">
                                        <Avatar className="h-9 w-9">
                                            <AvatarImage src={`https://ui-avatars.com/api/?name=${user.name}`} alt={user.name} />
                                            <AvatarFallback>{user.name.charAt(0)}</AvatarFallback>
                                        </Avatar>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent className="w-56" align="end" forceMount>
                                    <DropdownMenuLabel className="font-normal">
                                        <div className="flex flex-col space-y-1">
                                            <p className="text-sm font-medium leading-none">{user.name}</p>
                                            <p className="text-xs leading-none text-muted-foreground">{user.email}</p>
                                        </div>
                                    </DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    {auth.permissions?.length > 0 && (
                                        <DropdownMenuItem asChild>
                                            <Link href={route('dashboard')} className="w-full flex cursor-pointer">
                                                <LayoutDashboard className="mr-2 h-4 w-4" />
                                                <span>Admin Dashboard</span>
                                            </Link>
                                        </DropdownMenuItem>
                                    )}
                                    <DropdownMenuItem asChild>
                                        <Link href={route('profile.edit')} className="w-full flex cursor-pointer">
                                            <Settings className="mr-2 h-4 w-4" />
                                            <span>Profile Settings</span>
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem onClick={handleLogout} className="text-destructive cursor-pointer focus:text-destructive">
                                        <LogOut className="mr-2 h-4 w-4" />
                                        <span>Log out</span>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        ) : (
                            <div className="hidden md:flex gap-2">
                                <Button variant="ghost" asChild>
                                    <Link href={route('login')}>Log in</Link>
                                </Button>
                                {/* Uncomment if registration is open
                                <Button asChild>
                                    <Link href={route('register')}>Get Started</Link>
                                </Button> */}
                            </div>
                        )}

                        {/* Mobile Menu */}
                        <div className="md:hidden">
                            <Sheet>
                                <SheetTrigger asChild>
                                    <Button variant="ghost" size="icon" className="h-9 w-9">
                                        <MenuIcon className="h-5 w-5" />
                                        <span className="sr-only">Toggle menu</span>
                                    </Button>
                                </SheetTrigger>
                                <SheetContent side="right">
                                    <SheetHeader>
                                        <SheetTitle className="text-left">Navigation</SheetTitle>
                                    </SheetHeader>
                                    <div className="grid gap-4 py-4">
                                        {primaryNav.map((item: any) => (
                                            <div key={item.id} className="flex flex-col gap-2">
                                                <NavLink item={item} className="text-sm font-medium hover:text-primary transition-colors" />
                                                {item.subItems && item.subItems.length > 0 && (
                                                    <div className="pl-4 flex flex-col gap-2 border-l ml-1 mt-1">
                                                        {item.subItems.map((sub: any) => (
                                                            <NavLink key={sub.id} item={sub} className="text-sm text-muted-foreground hover:text-primary transition-colors" />
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                        {!user && (
                                            <div className="pt-4 border-t mt-2">
                                                <Link href={route('login')} className="text-sm font-medium hover:text-primary transition-colors">
                                                    Log in
                                                </Link>
                                            </div>
                                        )}
                                    </div>
                                </SheetContent>
                            </Sheet>
                        </div>
                    </div>
                </div>
            </header>

            <main className="flex-1">
                {children}
            </main>

            <footer className="border-t bg-muted/20">
                <div className="container max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
                        <div className="col-span-1 md:col-span-2">
                            <Link href="/">
                                <ApplicationLogo className="block h-8 w-auto fill-current opacity-70 mb-4" />
                            </Link>
                            <p className="text-sm text-muted-foreground max-w-xs">
                                {settings?.site_description || 'A modern headless CMS powered by Laravel, React, and Artificial Intelligence.'}
                            </p>
                        </div>
                        <div className="col-span-1">
                            {footerNav.length > 0 && (
                                <>
                                    <h3 className="text-sm font-semibold tracking-wider uppercase mb-4">Links</h3>
                                    <ul className="space-y-3">
                                        {footerNav.map((item: any) => (
                                            <li key={item.id}>
                                                <NavLink item={item} className="text-sm text-muted-foreground hover:text-foreground transition-colors" />
                                            </li>
                                        ))}
                                    </ul>
                                </>
                            )}
                        </div>
                        <div className="col-span-1">
                            <h3 className="text-sm font-semibold tracking-wider uppercase mb-4">Legal</h3>
                            <ul className="space-y-3">
                                <li>
                                    <Link href="/privacy" className="text-sm text-muted-foreground hover:text-foreground transition-colors">
                                        Privacy Policy
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/terms" className="text-sm text-muted-foreground hover:text-foreground transition-colors">
                                        Terms of Service
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div className="mt-12 pt-8 border-t text-center md:text-left flex flex-col md:flex-row justify-between items-center gap-4">
                        <p className="text-sm text-muted-foreground">
                            &copy; {new Date().getFullYear()} {settings?.site_name || 'Untitled CMS'}. All rights reserved.
                        </p>
                    </div>
                </div>
            </footer>
        </div>
    );
}

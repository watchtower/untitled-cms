import { PropsWithChildren } from 'react';
import { Link, Head } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';

export default function PublicLayout({ children }: PropsWithChildren) {
    return (
        <div className="min-h-screen bg-gray-50 text-gray-900 font-sans">
            <header className="bg-white shadow-sm sticky top-0 z-50">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-16">
                        <div className="flex">
                            <div className="shrink-0 flex items-center">
                                <Link href="/">
                                    <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800" />
                                </Link>
                            </div>
                            <div className="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                <Link
                                    href="/"
                                    className="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out"
                                >
                                    Home
                                </Link>
                                {/* Dynamic pages would be listed here in a real menu */}
                            </div>
                        </div>
                        <div className="flex items-center">
                            <Link
                                href={route('login')}
                                className="text-sm text-gray-700 dark:text-gray-500 underline"
                            >
                                Admin Login
                            </Link>
                        </div>
                    </div>
                </div>
            </header>

            <main>{children}</main>

            <footer className="bg-white border-t border-gray-100 mt-12">
                <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <p className="text-center text-sm text-gray-500">
                        &copy; {new Date().getFullYear()} Laravel MongoDB CMS. All rights reserved.
                    </p>
                </div>
            </footer>
        </div>
    );
}

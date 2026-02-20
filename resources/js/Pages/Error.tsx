import { Head } from '@inertiajs/react';

interface ErrorProps {
    status: number;
}

export default function Error({ status }: ErrorProps) {
    const title = {
        503: '503: Service Unavailable',
        500: '500: Server Error',
        404: '404: Page Not Found',
        403: '403: Forbidden',
    }[status];

    const description = {
        503: 'Sorry, we are doing some maintenance. Please check back soon.',
        500: 'Whoops, something went wrong on our servers.',
        404: 'Sorry, the page you are looking for could not be found.',
        403: 'Sorry, you are forbidden from accessing this page.',
    }[status];

    return (
        <div className="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
            <Head title={title} />
            <div className="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg text-center">
                <h1 className="text-4xl font-bold text-gray-800 dark:text-gray-200 mb-4">{status}</h1>
                <p className="text-lg text-gray-600 dark:text-gray-400 mb-6">{description}</p>
                <a href="/" className="text-blue-500 hover:text-blue-700 underline">
                    Go Home
                </a>
            </div>
        </div>
    );
}

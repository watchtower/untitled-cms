import { Head, Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { Button } from '@/Components/ui/button';
import {
    WrenchIcon,
    ServerCrashIcon,
    SearchXIcon,
    ShieldXIcon,
    HomeIcon,
    ArrowLeftIcon,
} from 'lucide-react';

interface ErrorProps {
    status: number;
}

interface ErrorConfig {
    icon: React.ElementType;
    heading: string;
    description: string;
    iconClass?: string;
}

const errorConfig: Record<number, ErrorConfig> = {
    503: {
        icon: WrenchIcon,
        heading: 'Website is under maintenance!',
        description:
            'The site is not available at the moment. We\'ll be back online shortly.',
        iconClass: 'text-amber-500',
    },
    500: {
        icon: ServerCrashIcon,
        heading: 'Oops! Something went wrong.',
        description:
            'We apologize for the inconvenience. Please try again later.',
        iconClass: 'text-destructive',
    },
    404: {
        icon: SearchXIcon,
        heading: 'Page not found.',
        description:
            'Sorry, the page you are looking for does not exist or has been moved.',
        iconClass: 'text-muted-foreground',
    },
    403: {
        icon: ShieldXIcon,
        heading: 'Access forbidden.',
        description:
            'You do not have permission to access this page.',
        iconClass: 'text-destructive',
    },
};

export default function Error({ status }: ErrorProps) {
    const config = errorConfig[status] ?? {
        icon: ServerCrashIcon,
        heading: 'An error occurred.',
        description: 'Something went wrong.',
        iconClass: 'text-muted-foreground',
    };

    const { icon: Icon, heading, description, iconClass } = config;

    const pageTitle =
        {
            503: '503 — Maintenance',
            500: '500 — Server Error',
            404: '404 — Not Found',
            403: '403 — Forbidden',
        }[status] ?? `${status} — Error`;

    return (
        <div className="min-h-screen flex flex-col items-center justify-center bg-background text-foreground px-4">
            <Head title={pageTitle} />

            {/* Icon */}
            <div
                className={cn(
                    'mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-muted',
                    status === 503 && 'bg-amber-50 dark:bg-amber-950/30',
                    (status === 500 || status === 403) && 'bg-red-50 dark:bg-red-950/30',
                )}
            >
                <Icon
                    className={cn('h-12 w-12', iconClass)}
                    strokeWidth={1.5}
                />
            </div>

            {/* Status code */}
            <p className="text-8xl font-extrabold tracking-tight text-muted-foreground/30 select-none leading-none mb-4">
                {status}
            </p>

            {/* Heading & Description */}
            <h1 className="text-2xl font-bold text-center mb-2">{heading}</h1>
            <p className="text-muted-foreground text-center max-w-md mb-8">
                {description}
            </p>

            {/* Action Buttons */}
            <div className="flex items-center gap-3">
                {status === 500 && (
                    <Button
                        variant="outline"
                        onClick={() => window.history.back()}
                    >
                        <ArrowLeftIcon className="mr-2 h-4 w-4" />
                        Go Back
                    </Button>
                )}

                {status !== 503 && (
                    <Button asChild>
                        <Link href="/">
                            <HomeIcon className="mr-2 h-4 w-4" />
                            Back to Home
                        </Link>
                    </Button>
                )}

                {status === 503 && (
                    <Button variant="outline" asChild>
                        <a href="mailto:support@example.com">
                            Contact Support
                        </a>
                    </Button>
                )}
            </div>
        </div>
    );
}

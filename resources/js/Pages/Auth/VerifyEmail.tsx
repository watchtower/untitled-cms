import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import AuthLayout from '@/Layouts/AuthLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { MailCheck, RefreshCw } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('verification.send'));
    };

    return (
        <AuthLayout>
            <Head title="Verify Email" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col items-center gap-2 text-center">
                    <MailCheck className="h-10 w-10 text-primary" />
                    <h1 className="text-2xl font-bold">Check your inbox</h1>
                    <p className="text-balance text-sm text-muted-foreground">
                        We sent a verification link to your email address. Click it to
                        activate your account.
                    </p>
                </div>

                {status === 'verification-link-sent' && (
                    <Alert className="border-green-500 text-green-700 dark:text-green-400">
                        <AlertDescription>
                            A new verification link has been sent to your email address.
                        </AlertDescription>
                    </Alert>
                )}

                <form onSubmit={submit}>
                    <Button type="submit" className="w-full" disabled={processing}>
                        <RefreshCw className={cn("mr-2 h-4 w-4", processing && "animate-spin")} />
                        Resend Verification Email
                    </Button>
                </form>

                <div className="text-center text-sm">
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="text-muted-foreground underline underline-offset-4 hover:text-foreground"
                    >
                        Log out
                    </Link>
                </div>
            </div>
        </AuthLayout>
    );
}

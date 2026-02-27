import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { SocialLoginButtons } from '@/Components/SocialLoginButtons';
import AuthLayout from '@/Layouts/AuthLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout>
            <Head title="Log in" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col items-center gap-2 text-center">
                    <h1 className="text-2xl font-bold">Welcome back</h1>
                    <p className="text-balance text-sm text-neutral-500 dark:text-neutral-400">
                        Login to your account
                    </p>
                </div>

                {status && (
                    <div className="text-sm font-medium text-green-600 text-center">
                        {status}
                    </div>
                )}

                <div className="grid gap-6">
                    <form onSubmit={submit}>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                    autoFocus
                                    placeholder="m@example.com"
                                />
                                {errors.email && <div className="text-sm text-red-500">{errors.email}</div>}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    required
                                />
                                {errors.password && <div className="text-sm text-red-500">{errors.password}</div>}
                                {canResetPassword && (
                                    <div className="flex justify-end mt-1">
                                        <Link
                                            href={route('password.request')}
                                            className="text-sm text-muted-foreground underline-offset-4 hover:underline"
                                        >
                                            Forgot your password?
                                        </Link>
                                    </div>
                                )}
                            </div>

                            <Button type="submit" className="w-full" disabled={processing}>
                                Login
                            </Button>
                        </div>
                    </form>

                    <SocialLoginButtons />

                    <div className="text-center text-sm">
                        Don&apos;t have an account?{" "}
                        <Link href={route('register')} className="underline underline-offset-4">
                            Sign up
                        </Link>
                    </div>
                </div>
            </div>
        </AuthLayout>
    );
}

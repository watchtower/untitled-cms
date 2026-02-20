import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import AuthLayout from '@/Layouts/AuthLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <AuthLayout>
            <Head title="Forgot Password" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col items-center gap-2 text-center">
                    <h1 className="text-2xl font-bold">Reset Password</h1>
                    <p className="text-balance text-sm text-neutral-500 dark:text-neutral-400">
                        Forgot your password? No problem. Just let us know your email address and we will email you a password reset link.
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

                            <Button type="submit" className="w-full" disabled={processing}>
                                Email Password Reset Link
                            </Button>
                        </div>
                    </form>

                    <div className="text-center text-sm">
                        Remember your password?{" "}
                        <Link href={route('login')} className="underline underline-offset-4">
                            Log in
                        </Link>
                    </div>
                </div>
            </div>
        </AuthLayout>
    );
}

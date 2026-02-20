import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { FormSplitLayout } from '@/Components/Common/FormLayouts';

export default function Edit({
    mustVerifyEmail,
    status,
}: PageProps<{ mustVerifyEmail: boolean; status?: string }>) {
    return (
        <AuthenticatedLayout header="Profile">
            <Head title="Profile" />

            <div className="flex flex-col gap-4">
                <h1 className="text-2xl font-bold">Profile Settings</h1>

                <FormSplitLayout
                    sidebar={
                        <Card className="border-destructive/50">
                            <CardHeader>
                                <CardTitle className="text-destructive">Danger Zone</CardTitle>
                                <CardDescription>Irreversible actions</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <DeleteUserForm className="w-full" />
                            </CardContent>
                        </Card>
                    }
                >
                    <div className="space-y-6">
                        <Card>
                            <CardContent className="pt-6">
                                <UpdateProfileInformationForm
                                    mustVerifyEmail={mustVerifyEmail}
                                    status={status}
                                    className="max-w-xl"
                                />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="pt-6">
                                <UpdatePasswordForm className="max-w-xl" />
                            </CardContent>
                        </Card>
                    </div>
                </FormSplitLayout>
            </div>
        </AuthenticatedLayout>
    );
}

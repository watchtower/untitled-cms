import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Checkbox } from '@/Components/ui/checkbox';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { PageProps, User } from '@/types';
import { FormSplitLayout, StickyFormFooter } from '@/Components/Common/FormLayouts';
import { useState, useEffect } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/Components/ui/alert-dialog";
import { router } from '@inertiajs/react';
import { User as UserIcon, Lock, Shield, Check, X, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';

interface Role {
    id: string;
    name: string;
    slug: string;
}

interface UserEditProps extends PageProps {
    user: User & { roles: Role[], is_active: boolean };
    roles: Role[];
}

import { Switch } from '@/Components/ui/switch';

export default function Edit({ auth, user, roles }: UserEditProps) {
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [emailValid, setEmailValid] = useState(true);
    const [passwordStrength, setPasswordStrength] = useState<'weak' | 'medium' | 'strong' | null>(null);

    const { data, setData, put, processing, errors, isDirty } = useForm({
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
        roles: user.roles.map(r => r.id),
        is_active: user.is_active ?? true,
    });

    const submit = () => {
        put(route('admin.users.update', user.id));
    };

    // Email validation
    useEffect(() => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        setEmailValid(emailRegex.test(data.email));
    }, [data.email]);

    // Password strength calculation
    useEffect(() => {
        if (!data.password) {
            setPasswordStrength(null);
            return;
        }
        const hasLower = /[a-z]/.test(data.password);
        const hasUpper = /[A-Z]/.test(data.password);
        const hasNumber = /[0-9]/.test(data.password);
        const hasSpecial = /[!@#$%^&*]/.test(data.password);
        const length = data.password.length;

        const strength = [hasLower, hasUpper, hasNumber, hasSpecial, length >= 8].filter(Boolean).length;
        if (strength <= 2) setPasswordStrength('weak');
        else if (strength <= 4) setPasswordStrength('medium');
        else setPasswordStrength('strong');
    }, [data.password]);

    // Keyboard shortcuts
    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 's') {
                e.preventDefault();
                if (isDirty && !processing) submit();
            }
            if ((e.metaKey || e.ctrlKey) && e.shiftKey && e.key === 'd') {
                e.preventDefault();
                setShowDeleteDialog(true);
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [isDirty, processing, submit]);

    const handleDelete = () => {
        router.delete(route('admin.users.destroy', user.id));
    };

    const handleRoleChange = (roleId: string, checked: boolean) => {
        if (checked) {
            setData('roles', [...data.roles, roleId]);
        } else {
            setData('roles', data.roles.filter((id) => id !== roleId));
        }
    };

    const memberSince = (user as any).created_at ? new Date((user as any).created_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }) : 'Unknown';

    return (
        <AuthenticatedLayout header="Edit User">
            <Head title="Edit User" />

            <div className="flex flex-col gap-4">
                {/* Header with Status Badge and Last Updated */}
                <div className="flex items-start justify-between">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold">Edit User</h1>
                            <Badge variant={user.email_verified_at ? 'default' : 'secondary'}>
                                {user.email_verified_at ? 'Verified' : 'Unverified'}
                            </Badge>
                        </div>
                        <div className="flex items-center gap-4 mt-2">
                            <p className="text-sm text-muted-foreground">Member since {memberSince}</p>
                            {(user as any).updated_at && (
                                <>
                                    <span className="text-muted-foreground">•</span>
                                    <p className="text-sm text-muted-foreground">
                                        Last updated {new Date((user as any).updated_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                                    </p>
                                </>
                            )}
                        </div>
                    </div>
                </div>

                <FormSplitLayout
                    sidebar={
                        <div className="space-y-4">
                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-base">Status</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex items-center space-x-2 border p-3 rounded-md">
                                        <Switch
                                            id="is_active"
                                            checked={data.is_active}
                                            onCheckedChange={(checked) => {
                                                const currentRoles = data.roles;
                                                const previousStatus = data.is_active;
                                                setData('is_active', checked);
                                                router.put(route('admin.users.update', user.id), {
                                                    name: data.name,
                                                    email: data.email,
                                                    roles: currentRoles,
                                                    is_active: checked,
                                                    stay: 1
                                                }, {
                                                    preserveScroll: true,
                                                    onError: (errors) => {
                                                        setData('is_active', previousStatus);
                                                    }
                                                });
                                            }}
                                        />
                                        <Label htmlFor="is_active" className="cursor-pointer flex-1">
                                            {data.is_active ? 'Active' : 'Inactive'}
                                        </Label>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-3">
                                    <div className="flex items-center gap-2">
                                        <Shield className="h-4 w-4 text-muted-foreground" />
                                        <CardTitle className="text-base">Roles</CardTitle>
                                    </div>
                                    <CardDescription className="text-xs">Assign user permissions</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="space-y-2">
                                        {roles.map((role) => (
                                            <div
                                                key={role.id}
                                                className={cn(
                                                    "flex items-center space-x-2 p-2 rounded-md transition-colors",
                                                    data.roles.includes(role.id) && "bg-primary/5 border border-primary/20"
                                                )}
                                            >
                                                <Checkbox
                                                    id={`role-${role.id}`}
                                                    checked={data.roles.includes(role.id)}
                                                    onCheckedChange={(checked) => handleRoleChange(role.id, checked as boolean)}
                                                />
                                                <label
                                                    htmlFor={`role-${role.id}`}
                                                    className="text-xs font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 flex-1 cursor-pointer"
                                                >
                                                    {role.name}
                                                </label>
                                                {data.roles.includes(role.id) && (
                                                    <Check className="h-3 w-3 text-primary" />
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                    {data.roles.length > 0 && (
                                        <p className="text-xs text-muted-foreground mt-2">
                                            {data.roles.length} role{data.roles.length !== 1 ? 's' : ''} assigned
                                        </p>
                                    )}
                                    {errors.roles && <p className="text-xs text-destructive mt-1">{errors.roles}</p>}
                                </CardContent>
                            </Card>

                            {/* Activity & Sessions Card */}
                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-base">Activity & Sessions</CardTitle>
                                    <CardDescription className="text-xs">Recent activity and active sessions</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {/* Activity Log Preview */}
                                    <div className="space-y-2">
                                        <h4 className="text-xs font-semibold text-muted-foreground">Recent Activity</h4>
                                        <div className="space-y-2">
                                            {(user as any).last_login_at ? (
                                                <div className="flex items-start gap-2 text-xs">
                                                    <div className="w-1.5 h-1.5 rounded-full bg-green-500 mt-1.5" />
                                                    <div className="flex-1">
                                                        <p className="text-foreground">Last login</p>
                                                        <p className="text-muted-foreground">
                                                            {new Date((user as any).last_login_at).toLocaleDateString('en-US', {
                                                                month: 'short',
                                                                day: 'numeric',
                                                                hour: '2-digit',
                                                                minute: '2-digit'
                                                            })}
                                                        </p>
                                                    </div>
                                                </div>
                                            ) : (
                                                <p className="text-xs text-muted-foreground">No recent activity</p>
                                            )}
                                        </div>
                                    </div>

                                    {/* Session Management */}
                                    <div className="space-y-2">
                                        <h4 className="text-xs font-semibold text-muted-foreground">Active Sessions</h4>
                                        <div className="space-y-2">
                                            <div className="flex items-center justify-between p-2 rounded-md bg-muted/50">
                                                <div className="flex items-center gap-2">
                                                    <div className="w-2 h-2 rounded-full bg-green-500" />
                                                    <span className="text-xs">Current session</span>
                                                </div>
                                            </div>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="w-full text-xs mt-2"
                                                onClick={() => {
                                                    if (confirm('Are you sure you want to logout from all devices? This will end all active sessions except the current one.')) {
                                                        router.post(route('admin.users.logout-all-devices', user.id));
                                                    }
                                                }}
                                            >
                                                Logout from all devices
                                            </Button>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    }
                >
                    <div className="space-y-4">
                        <Card>
                            <CardHeader className="pb-3">
                                <div className="flex items-center gap-2">
                                    <UserIcon className="h-4 w-4 text-muted-foreground" />
                                    <CardTitle className="text-base">User Profile</CardTitle>
                                </div>
                                <CardDescription className="text-xs">Update user information</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="space-y-1.5">
                                    <Label htmlFor="name" className="text-xs">Name</Label>
                                    <Input
                                        id="name"
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="h-9"
                                        required
                                        autoFocus
                                    />
                                    {errors.name && <p className="text-xs text-destructive mt-1">{errors.name}</p>}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="email" className="text-xs">Email</Label>
                                    <div className="relative">
                                        <Input
                                            id="email"
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            className={cn(
                                                "h-9 pr-8",
                                                data.email && (emailValid ? "border-green-500" : "border-destructive")
                                            )}
                                            required
                                        />
                                        {data.email && (
                                            <div className="absolute right-2 top-1/2 -translate-y-1/2">
                                                {emailValid ? (
                                                    <Check className="h-4 w-4 text-green-500" />
                                                ) : (
                                                    <X className="h-4 w-4 text-destructive" />
                                                )}
                                            </div>
                                        )}
                                    </div>
                                    {!emailValid && data.email && (
                                        <p className="text-xs text-destructive mt-1">Please enter a valid email address</p>
                                    )}
                                    {errors.email && <p className="text-xs text-destructive mt-1">{errors.email}</p>}
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-3">
                                <div className="flex items-center gap-2">
                                    <Lock className="h-4 w-4 text-muted-foreground" />
                                    <CardTitle className="text-base">Security</CardTitle>
                                </div>
                                <CardDescription className="text-xs">Update password (leave blank to keep current)</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="space-y-1.5">
                                    <Label htmlFor="password" className="text-xs">New Password</Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        className="h-9"
                                        autoComplete="new-password"
                                        placeholder="Enter new password"
                                    />
                                    {passwordStrength && (
                                        <div className="flex items-center gap-2 mt-2">
                                            <div className="flex-1 h-1.5 bg-muted rounded-full overflow-hidden">
                                                <div
                                                    className={cn(
                                                        "h-full transition-all",
                                                        passwordStrength === 'weak' && "w-1/3 bg-destructive",
                                                        passwordStrength === 'medium' && "w-2/3 bg-yellow-500",
                                                        passwordStrength === 'strong' && "w-full bg-green-500"
                                                    )}
                                                />
                                            </div>
                                            <span className={cn(
                                                "text-xs font-medium",
                                                passwordStrength === 'weak' && "text-destructive",
                                                passwordStrength === 'medium' && "text-yellow-600",
                                                passwordStrength === 'strong' && "text-green-600"
                                            )}>
                                                {passwordStrength.charAt(0).toUpperCase() + passwordStrength.slice(1)}
                                            </span>
                                        </div>
                                    )}
                                    {data.password && (
                                        <p className="text-xs text-muted-foreground mt-1">
                                            Use 8+ characters with uppercase, lowercase, numbers & symbols
                                        </p>
                                    )}
                                    {errors.password && <p className="text-xs text-destructive mt-1">{errors.password}</p>}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="password_confirmation" className="text-xs">Confirm New Password</Label>
                                    <div className="relative">
                                        <Input
                                            id="password_confirmation"
                                            type="password"
                                            value={data.password_confirmation}
                                            onChange={(e) => setData('password_confirmation', e.target.value)}
                                            className={cn(
                                                "h-9 pr-8",
                                                data.password && data.password_confirmation && (
                                                    data.password === data.password_confirmation ? "border-green-500" : "border-destructive"
                                                )
                                            )}
                                            autoComplete="new-password"
                                            placeholder="Confirm password"
                                        />
                                        {data.password && data.password_confirmation && (
                                            <div className="absolute right-2 top-1/2 -translate-y-1/2">
                                                {data.password === data.password_confirmation ? (
                                                    <Check className="h-4 w-4 text-green-500" />
                                                ) : (
                                                    <X className="h-4 w-4 text-destructive" />
                                                )}
                                            </div>
                                        )}
                                    </div>
                                    {data.password && data.password_confirmation && data.password !== data.password_confirmation && (
                                        <p className="text-xs text-destructive mt-1">Passwords do not match</p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </FormSplitLayout>
            </div>

            <StickyFormFooter
                isSaving={processing}
                isDirty={isDirty}
                onSave={submit}
                canDelete={true}
                onDelete={() => setShowDeleteDialog(true)}
            />

            <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This action cannot be undone. This will permanently delete the user account.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={handleDelete} className="bg-destructive text-destructive-foreground hover:bg-destructive/90">
                            Delete
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AuthenticatedLayout>
    );
}

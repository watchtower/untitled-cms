import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Edit, Trash2, Plus } from 'lucide-react';
import { DataTable } from '@/Components/Common/DataTable';
import { ColumnDef } from '@tanstack/react-table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { DataTableColumnHeader } from '@/Components/Common/DataTableColumnHeader';
import { MoreHorizontal } from 'lucide-react';

interface Role {
    id: string;
    name: string;
    slug: string;
    permissions: string[];
}

interface RolesIndexProps extends PageProps {
    roles: Role[];
}

export default function Index({ auth, roles }: RolesIndexProps) {
    const { permissions } = usePage().props.auth;
    const canCreate = permissions.includes('roles.manage');
    const canEdit = permissions.includes('roles.manage');
    const canDelete = permissions.includes('roles.manage');

    const columns: ColumnDef<Role>[] = [
        {
            accessorKey: 'name',
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Name" />
            ),
            cell: ({ row }) => <div className="font-medium">{row.getValue('name')}</div>,
        },
        {
            accessorKey: 'slug',
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Slug" />
            ),
            cell: ({ row }) => <code className="bg-muted px-1.5 py-0.5 rounded text-xs">{row.getValue('slug')}</code>,
        },
        {
            accessorKey: 'permissions',
            header: 'Permissions Count',
            cell: ({ row }) => {
                const count = (row.original.permissions || []).length;
                return <Badge variant="outline">{count}</Badge>;
            },
        },
        {
            id: 'actions',
            cell: ({ row }) => {
                const role = row.original;
                return (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" className="h-8 w-8 p-0">
                                <span className="sr-only">Open menu</span>
                                <MoreHorizontal className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuLabel>Actions</DropdownMenuLabel>
                            {canEdit && (
                                <DropdownMenuItem onClick={() => router.visit(route('admin.roles.edit', role.id))}>
                                    <Edit className="mr-2 h-4 w-4" /> Edit
                                </DropdownMenuItem>
                            )}
                            {canDelete && (
                                <DropdownMenuItem
                                    onClick={() => {
                                        if (confirm('Are you sure you want to delete this role?')) {
                                            router.delete(route('admin.roles.destroy', role.id));
                                        }
                                    }}
                                    className="text-destructive focus:text-destructive"
                                >
                                    <Trash2 className="mr-2 h-4 w-4" /> Delete
                                </DropdownMenuItem>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                );
            },
        },
    ];

    const MobileCard = ({ row }: { row: Role }) => {
        return (
            <div className="p-4 space-y-3">
                <div className="flex justify-between items-start">
                    <div>
                        <h3 className="font-semibold text-lg">{row.name}</h3>
                        <code className="text-xs bg-muted px-1.5 py-0.5 rounded">{row.slug}</code>
                    </div>
                    <div className="flex gap-1">
                        {canEdit && (
                            <Link href={route('admin.roles.edit', row.id)}>
                                <Button variant="ghost" size="icon" className="h-8 w-8">
                                    <Edit className="h-4 w-4" />
                                </Button>
                            </Link>
                        )}
                    </div>
                </div>
                <div className="text-sm text-muted-foreground">
                    <span className="font-medium">{row.permissions?.length || 0}</span> Permissions
                </div>
            </div>
        );
    };

    return (
        <AuthenticatedLayout header="Roles">
            <Head title="Roles" />

            <div className="flex flex-col gap-4">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold">Role Management</h1>
                    {canCreate && (
                        <Link href={route('admin.roles.create')}>
                            <Button>
                                <Plus className="mr-2 h-4 w-4" /> Add Role
                            </Button>
                        </Link>
                    )}
                </div>

                <div className="rounded-md border bg-card">
                    <DataTable
                        data={roles}
                        columns={columns}
                        mobileCardRenderer={(row) => <MobileCard row={row} />}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage, router } from '@inertiajs/react';
import { PageProps, User, Role } from '@/types';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Edit, Trash2, Plus, RotateCcw, Trash, Shield, User as UserIcon, ShieldCheck } from 'lucide-react';
import { DataTable } from '@/Components/Common/DataTable';
import { ColumnDef } from '@tanstack/react-table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { useState, useMemo, useEffect } from 'react';
import { DataTableColumnHeader } from '@/Components/Common/DataTableColumnHeader';
import { MoreHorizontal, CheckCircle, XCircle } from 'lucide-react';
import { Checkbox } from "@/Components/ui/checkbox";
import { UserStatusBadge } from '@/Components/Users/UserStatusBadge';
import { UserStatsCards } from '@/Components/Users/UserStatsCards';
import { DataTableToolbar } from '@/Components/Common/DataTableToolbar';
import { DataTableBatchActions } from '@/Components/Common/DataTableBatchActions';
import { InviteUserDialog } from '@/Components/Users/InviteUserDialog';
import { Table } from '@tanstack/react-table';

interface UsersIndexProps extends PageProps {
    users: {
        data: User[];
        links: any[]; // Pagination links
    };
    trashedUsers?: {
        data: User[];
    };
    roles: Role[];
    stats: {
        total: number;
        active: number;
        inactive: number;
        deleted: number;
    };
}

// Load persisted filters from localStorage (moved outside component for performance)
const loadPersistedFilters = () => {
    try {
        const saved = localStorage.getItem('users_filters');
        if (saved) {
            const parsed = JSON.parse(saved);
            return {
                showTrashed: parsed.showTrashed || false,
                statusFilter: parsed.statusFilter || []
            };
        }
    } catch {
        // ignore localStorage read errors
    }
    return { showTrashed: false, statusFilter: [] };
};

export default function Index({ auth, users, trashedUsers, roles, stats }: UsersIndexProps) {
    const { canCreate, canEdit, canDelete } = usePage<PageProps>().props;

    const persistedFilters = loadPersistedFilters();
    const [selectedRows, setSelectedRows] = useState<string[]>([]);
    const [showTrashed, setShowTrashed] = useState(persistedFilters.showTrashed);
    const [statusFilter, setStatusFilter] = useState<string[]>(persistedFilters.statusFilter);
    const [showInviteDialog, setShowInviteDialog] = useState(false);

    // Persist filters to localStorage whenever they change
    useEffect(() => {
        try {
            localStorage.setItem('users_filters', JSON.stringify({
                showTrashed,
                statusFilter
            }));
        } catch {
            // ignore localStorage write errors
        }
    }, [showTrashed, statusFilter]);

    const columns = useMemo<ColumnDef<User>[]>(() => [
        {
            id: "select",
            header: ({ table }) => (
                <Checkbox
                    checked={
                        table.getIsAllPageRowsSelected() ||
                        (table.getIsSomePageRowsSelected() && "indeterminate")
                    }
                    onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
                    aria-label="Select all"
                    className="translate-y-[2px]"
                />
            ),
            cell: ({ row }) => (
                <Checkbox
                    checked={row.getIsSelected()}
                    onCheckedChange={(value) => row.toggleSelected(!!value)}
                    aria-label="Select row"
                    className="translate-y-[2px]"
                />
            ),
            enableSorting: false,
            enableHiding: false,
        },
        {
            accessorKey: 'name',
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Name" />
            ),
            cell: ({ row }) => <div className="font-medium">{row.getValue('name')}</div>,
        },
        {
            accessorKey: 'email',
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Email" />
            ),
        },
        {
            id: 'status',
            accessorKey: 'is_active',
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Status" />
            ),
            cell: ({ row }) => (
                <UserStatusBadge
                    isActive={row.original.is_active}
                    isDeleted={!!(row.original as any).deleted_at}
                />
            ),
            filterFn: (row, id, value) => {
                const isActive = row.original.is_active;
                const isDeleted = !!row.original.deleted_at;

                // Check for each filter value
                return value.some((v: string) => {
                    if (v === 'deleted') return isDeleted;
                    return String(isActive) === v && !isDeleted;
                });
            },
            enableHiding: true,
        },
        {
            accessorKey: 'roles',
            header: 'Roles',
            cell: ({ row }) => {
                const userRoles = (row.original as any).roles;
                return (
                    <div className="flex flex-wrap gap-1">
                        {userRoles?.map((role: any) => {
                            const Icon = role.slug === 'admin' ? Shield : UserIcon;
                            return (
                                <Badge key={role.id} variant="secondary" className="flex items-center gap-1">
                                    <Icon className="h-3 w-3" />
                                    {role.name}
                                </Badge>
                            );
                        })}
                    </div>
                );
            },
            filterFn: (row, id, value) => {
                const rowRoles = (row.original as any).roles?.map((r: any) => r.id) || [];
                return value.some((v: string) => rowRoles.includes(v));
            },
        },
        {
            id: 'actions',
            header: 'Actions',
            enableHiding: false,
            cell: ({ row }) => {
                const user = row.original;
                const isTrashed = !!user.deleted_at;

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
                            {!isTrashed && canEdit && (
                                <DropdownMenuItem onClick={() => router.visit(route('users.edit', user.id))}>
                                    <Edit className="mr-2 h-4 w-4" /> Edit
                                </DropdownMenuItem>
                            )}
                            {!isTrashed && canDelete && (
                                <DropdownMenuItem
                                    onClick={() => {
                                        if (confirm('Are you sure you want to delete this user?')) {
                                            router.delete(route('users.destroy', user.id));
                                        }
                                    }}
                                    className="text-destructive focus:text-destructive"
                                >
                                    <Trash2 className="mr-2 h-4 w-4" /> Delete
                                </DropdownMenuItem>
                            )}
                            {isTrashed && canDelete && (
                                <>
                                    <DropdownMenuItem onClick={() => {
                                        if (confirm('Are you sure you want to restore this user?')) {
                                            router.post(route('users.restore', user.id));
                                        }
                                    }}>
                                        <RotateCcw className="mr-2 h-4 w-4" /> Restore
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        onClick={() => {
                                            if (confirm('Are you sure you want to permanently delete this user? This action cannot be undone.')) {
                                                router.delete(route('users.force-delete', user.id));
                                            }
                                        }}
                                        className="text-destructive focus:text-destructive"
                                    >
                                        <Trash className="mr-2 h-4 w-4" /> Force Delete
                                    </DropdownMenuItem>
                                </>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                );
            },
        },
    ], [canEdit, canDelete]);

    const displayData = showTrashed ? (trashedUsers?.data || []) : users.data;


    const handleBatchActivate = (ids: string[]) => {
        if (confirm(`Activate ${ids.length} selected users?`)) {
            router.post(route('users.batch-activate'), { user_ids: ids });
        }
    };

    const handleBatchDeactivate = (ids: string[]) => {
        if (confirm(`Deactivate ${ids.length} selected users?`)) {
            router.post(route('users.batch-deactivate'), { user_ids: ids });
        }
    };

    const handleBatchDelete = (ids: string[]) => {
        if (confirm(`Delete ${ids.length} selected users?`)) {
            router.post(route('users.batch-delete'), { user_ids: ids });
        }
    };

    return (
        <AuthenticatedLayout header="Users">
            <Head title="Users" />

            <div className="flex flex-col gap-6">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">User Management</h1>
                        <p className="text-muted-foreground">Manage your users and their access levels here.</p>
                    </div>
                    <div className="flex gap-2">
                        {canCreate && !showTrashed && (
                            <div className="flex gap-2">
                                <InviteUserDialog roles={roles} />
                                <Link href={route('users.create')}>
                                    <Button size="sm" className="h-8">
                                        <Plus className="mr-2 h-4 w-4" /> Add User
                                    </Button>
                                </Link>
                            </div>
                        )}
                    </div>
                </div>

                <UserStatsCards
                    stats={stats}
                    onCardClick={(type) => {
                        if (type === 'deleted') {
                            setShowTrashed(true);
                            setStatusFilter(['deleted']);
                        } else if (type === 'active') {
                            setShowTrashed(false);
                            setStatusFilter(['true']);
                        } else if (type === 'inactive') {
                            setShowTrashed(false);
                            setStatusFilter(['false']);
                        } else {
                            setShowTrashed(false);
                            setStatusFilter([]);
                        }
                    }}
                />

                <div className="space-y-4">
                    <DataTable
                        data={displayData}
                        columns={columns}
                        initialColumnFilters={statusFilter.length > 0 ? [{ id: 'status', value: statusFilter }] : []}
                        onRowSelectionChange={(rows: any[]) => {
                            setSelectedRows(rows.map(r => r.original.id));
                        }}
                    >
                        {({ table }) => (
                            <>
                                <DataTableToolbar
                                    table={table}
                                    searchKey="name"
                                    filters={[
                                        {
                                            column: 'status',
                                            title: 'Status',
                                            options: [
                                                { label: 'Active', value: 'true', icon: CheckCircle },
                                                { label: 'Inactive', value: 'false', icon: XCircle },
                                                { label: 'Deleted', value: 'deleted', icon: Trash2 },
                                            ]
                                        },
                                        {
                                            column: 'roles',
                                            title: 'Roles',
                                            options: roles.map(r => ({
                                                label: r.name,
                                                value: r.id,
                                                icon: r.slug === 'admin' ? ShieldCheck : UserIcon
                                            }))
                                        }
                                    ]}
                                />
                                {table.getSelectedRowModel().rows.length > 0 && (
                                    <DataTableBatchActions
                                        selectedCount={table.getSelectedRowModel().rows.length}
                                        onActivate={() => handleBatchActivate(table.getSelectedRowModel().rows.map((r: any) => (r.original as any).id))}
                                        onDeactivate={() => handleBatchDeactivate(table.getSelectedRowModel().rows.map((r: any) => (r.original as any).id))}
                                        onDelete={() => handleBatchDelete(table.getSelectedRowModel().rows.map((r: any) => (r.original as any).id))}
                                        onClear={() => table.resetRowSelection()}
                                    />
                                )}
                            </>
                        )}
                    </DataTable>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}


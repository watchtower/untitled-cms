import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage, router, useForm } from '@inertiajs/react';
import { PageProps } from '@/types';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Edit, Trash2, Plus, MoreHorizontal } from 'lucide-react';
import { DataTableColumnHeader } from '@/Components/Common/DataTableColumnHeader';
import { DataTable } from '@/Components/Common/DataTable';
import { ColumnDef } from '@tanstack/react-table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { useState } from 'react';
import { toast } from 'sonner';

interface MenuModel {
    id: string;
    name: string;
    slug: string;
    items?: any[];
    is_active: boolean;
}

interface MenusIndexProps extends PageProps {
    menus: MenuModel[];
}

export default function Index({ menus }: MenusIndexProps) {
    const { permissions } = usePage().props.auth;
    const canManage = permissions.includes('menus.manage') || true; // Assuming true for now or adapt based on roles

    const [createOpen, setCreateOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        slug: '',
        is_active: true,
    });

    const submitCreate = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('menus.store'), {
            onSuccess: () => {
                setCreateOpen(false);
                reset();
                toast.success('Menu created');
            },
        });
    };

    const columns: ColumnDef<MenuModel>[] = [
        {
            accessorKey: "name",
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Name" />
            ),
            cell: ({ row }) => <div className="font-medium">{row.getValue("name")}</div>,
        },
        {
            accessorKey: "slug",
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Slug" />
            ),
            cell: ({ row }) => <div className="text-muted-foreground">{row.getValue("slug")}</div>,
        },
        {
            accessorKey: "items",
            header: "Items Count",
            cell: ({ row }) => {
                const items = row.original.items || [];
                return <div className="text-muted-foreground">{items.length} top-level</div>;
            }
        },
        {
            accessorKey: "is_active",
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Status" />
            ),
            cell: ({ row }) => {
                const isActive = row.getValue("is_active");
                return (
                    <Badge variant={isActive ? 'default' : 'secondary'}>
                        {isActive ? 'Active' : 'Inactive'}
                    </Badge>
                );
            }
        },
        {
            id: "actions",
            enableHiding: false,
            cell: ({ row }) => {
                const menu = row.original;

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
                            {canManage && (
                                <DropdownMenuItem onClick={() => router.visit(route('menus.edit', menu.id))}>
                                    <Edit className="mr-2 h-4 w-4" /> Edit
                                </DropdownMenuItem>
                            )}
                            {canManage && (
                                <DropdownMenuItem
                                    onClick={() => {
                                        if (confirm('Are you sure you want to delete this menu?')) {
                                            router.delete(route('menus.destroy', menu.id));
                                        }
                                    }}
                                    className="text-destructive focus:text-destructive"
                                >
                                    <Trash2 className="mr-2 h-4 w-4" /> Delete
                                </DropdownMenuItem>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                )
            },
        },
    ];

    const mobileCardRenderer = (menu: MenuModel) => (
        <div className="p-4 space-y-3">
            <div className="flex items-start justify-between">
                <div>
                    <h3 className="font-semibold">{menu.name}</h3>
                    <div className="text-xs text-muted-foreground mt-0.5">
                        {menu.slug} • {(menu.items || []).length} items
                    </div>
                </div>
                <Badge variant={menu.is_active ? 'default' : 'secondary'}>
                    {menu.is_active ? 'Active' : 'Inactive'}
                </Badge>
            </div>

            <div className="flex items-center justify-end gap-2 pt-2 border-t">
                {canManage && (
                    <Button variant="outline" size="sm" asChild>
                        <Link href={route('menus.edit', menu.id)}>Edit</Link>
                    </Button>
                )}
                {canManage && (
                    <Button variant="outline" size="sm" className="text-destructive border-dashed border-red-200" asChild>
                        <Link href={route('menus.destroy', menu.id)} method="delete" as="button">Delete</Link>
                    </Button>
                )}
            </div>
        </div>
    );

    return (
        <AuthenticatedLayout header="Menus">
            <Head title="Menus" />

            <div className="flex flex-col gap-4">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold">Menu Management</h1>
                    {canManage && (
                        <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                            <DialogTrigger asChild>
                                <Button>
                                    <Plus className="mr-2 h-4 w-4" /> Create Menu
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Create New Menu</DialogTitle>
                                </DialogHeader>
                                <form onSubmit={submitCreate} className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Name</Label>
                                        <Input
                                            id="name"
                                            value={data.name}
                                            onChange={e => setData('name', e.target.value)}
                                            placeholder="e.g. Primary Navigation"
                                        />
                                        {errors.name && <div className="text-sm text-destructive">{errors.name}</div>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="slug">Slug</Label>
                                        <Input
                                            id="slug"
                                            value={data.slug}
                                            onChange={e => setData('slug', e.target.value)}
                                            placeholder="e.g. primary-navigation"
                                        />
                                        <div className="text-xs text-muted-foreground">Unique identifier to reference this menu in frontend layouts.</div>
                                        {errors.slug && <div className="text-sm text-destructive">{errors.slug}</div>}
                                    </div>
                                    <div className="flex justify-end pt-4">
                                        <Button type="submit" disabled={processing}>
                                            Create
                                        </Button>
                                    </div>
                                </form>
                            </DialogContent>
                        </Dialog>
                    )}
                </div>

                <div className="rounded-md border bg-card">
                    <DataTable
                        columns={columns}
                        data={menus}
                        mobileCardRenderer={mobileCardRenderer}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

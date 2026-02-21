import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Edit, Trash2, Plus, ImageIcon, MoreHorizontal } from 'lucide-react';
import { DataTableColumnHeader } from '@/Components/Common/DataTableColumnHeader';
import { DataTable } from '@/Components/Common/DataTable';
import { ColumnDef } from '@tanstack/react-table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";

interface BannerModel {
    id: string;
    title: string;
    slides: { image: string }[];
    order: number;
    is_active: boolean;
    start_at: string | null;
    end_at: string | null;
}

interface BannersIndexProps extends PageProps {
    banners: BannerModel[];
}

export default function Index({ auth, banners }: BannersIndexProps) {
    const { permissions } = usePage().props.auth;
    const canCreate = permissions.includes('banners.manage');
    const canEdit = permissions.includes('banners.manage');
    const canDelete = permissions.includes('banners.manage');

    const columns: ColumnDef<BannerModel>[] = [
        {
            accessorKey: "order",
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Order" />
            ),
        },
        {
            id: "preview",
            header: "Preview",
            cell: ({ row }) => {
                const banner = row.original;
                const previewImage = banner.slides?.[0]?.image;
                return (
                    <div className="h-10 w-16 bg-muted rounded overflow-hidden relative">
                        {previewImage ? (
                            <img
                                src={previewImage}
                                alt={banner.title}
                                className="h-full w-full object-cover"
                            />
                        ) : (
                            <div className="flex items-center justify-center h-full">
                                <ImageIcon className="h-4 w-4 text-muted-foreground" />
                            </div>
                        )}
                    </div>
                );
            }
        },
        {
            accessorKey: "title",
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Title" />
            ),
            cell: ({ row }) => <div className="font-medium">{row.getValue("title")}</div>,
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
            id: "schedule",
            header: "Schedule",
            cell: ({ row }) => {
                const banner = row.original;
                return (
                    <div className="text-xs text-muted-foreground">
                        {banner.start_at ? new Date(banner.start_at).toLocaleDateString() : 'Always'}
                        {' - '}
                        {banner.end_at ? new Date(banner.end_at).toLocaleDateString() : 'Forever'}
                    </div>
                );
            }
        },
        {
            id: "actions",
            enableHiding: false,
            cell: ({ row }) => {
                const banner = row.original;

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
                                <DropdownMenuItem onClick={() => router.visit(route('banners.edit', banner.id))}>
                                    <Edit className="mr-2 h-4 w-4" /> Edit
                                </DropdownMenuItem>
                            )}
                            {canDelete && (
                                <DropdownMenuItem
                                    onClick={() => {
                                        if (confirm('Are you sure you want to delete this banner?')) {
                                            router.delete(route('banners.destroy', banner.id));
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

    const mobileCardRenderer = (banner: BannerModel) => (
        <div className="p-4 space-y-3">
            <div className="flex items-start justify-between">
                <div className="flex gap-3">
                    <div className="h-12 w-12 bg-muted rounded overflow-hidden flex-shrink-0">
                        {banner.slides?.[0]?.image ? (
                            <img src={banner.slides[0].image} alt={banner.title} className="h-full w-full object-cover" />
                        ) : (
                            <div className="flex items-center justify-center h-full"><ImageIcon className="h-5 w-5" /></div>
                        )}
                    </div>
                    <div>
                        <h3 className="font-semibold">{banner.title}</h3>
                        <div className="text-xs text-muted-foreground mt-0.5">
                            {banner.start_at ? new Date(banner.start_at).toLocaleDateString() : 'Always'} - {banner.end_at ? new Date(banner.end_at).toLocaleDateString() : 'Forever'}
                        </div>
                    </div>
                </div>
                <Badge variant={banner.is_active ? 'default' : 'secondary'}>
                    {banner.is_active ? 'Active' : 'Inactive'}
                </Badge>
            </div>

            <div className="flex items-center justify-end gap-2 pt-2 border-t">
                {canEdit && (
                    <Button variant="outline" size="sm" asChild>
                        <Link href={route('banners.edit', banner.id)}>Edit</Link>
                    </Button>
                )}
                {canDelete && (
                    <Button variant="outline" size="sm" className="text-destructive border-dashed border-red-200" asChild>
                        <Link href={route('banners.destroy', banner.id)} method="delete" as="button">Delete</Link>
                    </Button>
                )}
            </div>
        </div>
    );

    return (
        <AuthenticatedLayout header="Banners">
            <Head title="Banners" />

            <div className="flex flex-col gap-4">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold">Banner Management</h1>
                    {canCreate && (
                        <Link href={route('banners.create')}>
                            <Button>
                                <Plus className="mr-2 h-4 w-4" /> Add Banner
                            </Button>
                        </Link>
                    )}
                </div>

                <div className="rounded-md border bg-card">
                    <DataTable
                        columns={columns}
                        data={banners}
                        mobileCardRenderer={mobileCardRenderer}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

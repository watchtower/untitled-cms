import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Edit, Trash2, Plus, FileText, Globe, Calendar, MoreHorizontal } from 'lucide-react';
import { DataTable } from '@/Components/Common/DataTable';
import { ColumnDef } from '@tanstack/react-table';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { DataTableColumnHeader } from '@/Components/Common/DataTableColumnHeader';

interface PageModel {
    id: string;
    title: string;
    slug: string;
    status: 'draft' | 'published';
    published_at: string | null;
    updated_at: string;
}

interface PagesIndexProps extends PageProps {
    pages: {
        data: PageModel[];
        meta: any; // Pagination meta
    };
}

export default function Index({ auth, pages }: PagesIndexProps) {
    const { permissions } = usePage().props.auth;
    const canCreate = permissions.includes('pages.create');
    const canEdit = permissions.includes('pages.edit');
    const canDelete = permissions.includes('pages.delete');

    const columns: ColumnDef<PageModel>[] = [
        {
            accessorKey: 'title',
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Title" />
            ),
            cell: ({ row }) => (
                <a
                    href={`/${row.original.slug}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="font-medium flex items-center hover:text-primary hover:underline"
                >
                    <FileText className="mr-2 h-4 w-4 text-muted-foreground" />
                    {row.original.title}
                </a>
            ),
        },
        {
            accessorKey: 'status',
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Status" />
            ),
            cell: ({ row }) => (
                <Badge variant={row.original.status === 'published' ? 'default' : 'secondary'}>
                    {row.original.status === 'published' ? 'Published' : 'Draft'}
                </Badge>
            ),
        },
        {
            accessorKey: 'published_at',
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Published" />
            ),
            cell: ({ row }) => row.original.published_at
                ? new Date(row.original.published_at).toLocaleDateString()
                : <span className="text-muted-foreground italic">Unpublished</span>,
        },
        {
            accessorKey: 'updated_at',
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Last Updated" />
            ),
            cell: ({ row }) => new Date(row.original.updated_at).toLocaleDateString(),
        },
        {
            id: 'actions',
            cell: ({ row }) => (
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
                            <DropdownMenuItem onClick={() => router.visit(route('pages.edit', row.original.id))}>
                                <Edit className="mr-2 h-4 w-4" /> Edit
                            </DropdownMenuItem>
                        )}
                        {canDelete && (
                            <DropdownMenuItem
                                onClick={() => {
                                    if (confirm('Are you sure you want to delete this page?')) {
                                        router.delete(route('pages.destroy', row.original.id));
                                    }
                                }}
                                className="text-destructive focus:text-destructive"
                            >
                                <Trash2 className="mr-2 h-4 w-4" /> Delete
                            </DropdownMenuItem>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            ),
        },
    ];

    const MobileCard = ({ row }: { row: PageModel }) => (
        <Card className="mb-4">
            <CardHeader className="pb-2">
                <div className="flex justify-between items-start">
                    <CardTitle className="text-lg font-medium flex items-center gap-2">
                        <FileText className="h-5 w-5 text-primary" />
                        {row.title}
                    </CardTitle>
                    <Badge variant={row.status === 'published' ? 'default' : 'secondary'}>
                        {row.status}
                    </Badge>
                </div>
                <CardDescription className="font-mono text-xs">
                    /{row.slug}
                </CardDescription>
            </CardHeader>
            <CardContent className="pb-2 text-sm text-muted-foreground space-y-1">
                <div className="flex items-center gap-2">
                    <Globe className="h-3 w-3" />
                    {row.published_at ? new Date(row.published_at).toLocaleDateString() : 'Unpublished'}
                </div>
                <div className="flex items-center gap-2">
                    <Calendar className="h-3 w-3" />
                    Updated: {new Date(row.updated_at).toLocaleDateString()}
                </div>
            </CardContent>
            <CardFooter className="flex justify-end gap-2 pt-2 border-t">
                {canEdit && (
                    <Link href={route('pages.edit', row.id)}>
                        <Button variant="outline" size="sm">
                            <Edit className="mr-2 h-3 w-3" /> Edit
                        </Button>
                    </Link>
                )}
                {canDelete && (
                    <Button
                        variant="outline"
                        size="sm"
                        className="text-destructive border-destructive/20 hover:bg-destructive/10"
                        onClick={() => {
                            if (confirm('Are you sure you want to delete this page?')) {
                                router.delete(route('pages.destroy', row.id));
                            }
                        }}
                    >
                        <Trash2 className="mr-2 h-3 w-3" /> Delete
                    </Button>
                )}
            </CardFooter>
        </Card>
    );

    return (
        <AuthenticatedLayout header="Pages">
            <Head title="Pages" />

            <div className="flex flex-col gap-4">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold tracking-tight">Content Pages</h1>
                    {canCreate && (
                        <Link href={route('pages.create')}>
                            <Button>
                                <Plus className="mr-2 h-4 w-4" /> Add Page
                            </Button>
                        </Link>
                    )}
                </div>

                <div className="rounded-md border bg-card">
                    <DataTable
                        data={pages.data}
                        columns={columns}
                        mobileCardRenderer={(row) => <MobileCard row={row} />}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

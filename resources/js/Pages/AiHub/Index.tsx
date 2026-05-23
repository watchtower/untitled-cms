import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { ColumnDef } from '@tanstack/react-table';
import { DataTable } from '@/Components/Common/DataTable';
import { DataTableColumnHeader } from '@/Components/Common/DataTableColumnHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { MoreHorizontal, Edit, Bot, RotateCcw, KeyRound, Pencil } from 'lucide-react';
import { Switch } from '@/Components/ui/switch';
import { Progress } from '@/Components/ui/progress';
import { router, useForm } from '@inertiajs/react';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/Components/ui/sheet';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useState, useEffect } from 'react';

interface AiHub {
    id: number;
    name: string;
    is_active: boolean;
    default_model: string | null;
    image_model: string | null;
    has_key: boolean;
    api_key: string | null;
    usage_percent: number;
    usage_text: string;
}

export default function Index({ integrations }: PageProps<{ integrations: AiHub[] }>) {
    const { auth } = usePage().props;
    const canEdit = auth.permissions.includes('ai-integrations.edit');

    const [editingHub, setEditingHub] = useState<AiHub | null>(null);
    const [editingKey, setEditingKey] = useState(false);

    const { data, setData, put, processing, errors, reset } = useForm({
        is_active: false,
        default_model: '',
        image_model: '',
        api_key: '',
        clear_key: false,
    });

    useEffect(() => {
        if (editingHub) {
            setEditingKey(!editingHub.has_key);
            setData({
                is_active: editingHub.is_active,
                default_model: editingHub.default_model || '',
                image_model: editingHub.image_model || '',
                api_key: '',
                clear_key: false,
            });
        }
    }, [editingHub]);

    const handleSave = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingHub) return;
        put(route('admin.ai-hubs.update', editingHub.id), {
            preserveScroll: true,
            onSuccess: () => setEditingHub(null),
        });
    };

    const handleResetUsage = (hubId: number) => {
        if (confirm('Are you sure you want to reset the monthly usage counter for this provider to zero?')) {
            router.post(route('ai-hubs.reset-usage', hubId) /* dead route — not currently wired */, {}, { preserveScroll: true });
        }
    };

    const columns: ColumnDef<AiHub>[] = [
        {
            accessorKey: 'name',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Provider Name" />,
            cell: ({ row }) => (
                <div className="flex items-center space-x-2">
                    <Bot className="h-4 w-4 text-muted-foreground" />
                    <span className="font-medium">{row.getValue('name')}</span>
                </div>
            ),
        },
        {
            accessorKey: 'is_active',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
            cell: ({ row }) => {
                const integration = row.original;
                return (
                    <div className="flex items-center space-x-2">
                        <Switch
                            checked={integration.is_active}
                            onCheckedChange={() => {
                                router.post(route('admin.ai-hubs.activate', integration.id), {}, { preserveScroll: true });
                            }}
                        />
                        <Badge variant={integration.is_active ? 'default' : 'secondary'}>
                            {integration.is_active ? 'Active' : 'Inactive'}
                        </Badge>
                    </div>
                );
            },
        },
        {
            accessorKey: 'usage_percent',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Monthly Usage" />,
            cell: ({ row }) => {
                const percent = row.getValue('usage_percent') as number;
                const text = row.original.usage_text;
                return (
                    <div className="w-[150px] space-y-1">
                        <div className="flex justify-between text-xs text-muted-foreground mb-1">
                            <span>{text}</span>
                            <span>{percent}%</span>
                        </div>
                        <Progress value={percent} className="h-2" />
                    </div>
                );
            },
        },
        {
            accessorKey: 'default_model',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Default Model" />,
            cell: ({ row }) => <span>{row.getValue('default_model') || 'Not Set'}</span>,
        },
        {
            accessorKey: 'has_key',
            header: ({ column }) => <DataTableColumnHeader column={column} title="API Key" />,
            cell: ({ row }) => {
                const hasKey = row.getValue('has_key') as boolean;
                return (
                    <Badge variant={hasKey ? 'default' : 'destructive'} className={hasKey ? 'bg-green-600 hover:bg-green-700' : ''}>
                        {hasKey ? 'Configured' : 'Missing'}
                    </Badge>
                );
            },
        },
        {
            id: 'actions',
            cell: ({ row }) => {
                const integration = row.original;

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
                            <DropdownMenuSeparator />
                            {canEdit && (
                                <>
                                    <DropdownMenuItem onClick={() => setEditingHub(integration)}>
                                        <Edit className="mr-2 h-4 w-4" />
                                        Edit Configuration
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem onClick={() => handleResetUsage(integration.id)} className="text-destructive">
                                        <RotateCcw className="mr-2 h-4 w-4" />
                                        Reset Usage
                                    </DropdownMenuItem>
                                </>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                );
            },
        },
    ];

    const mobileCardRenderer = (model: AiHub) => (
        <Card key={model.id} className="mb-4">
            <CardContent className="p-4 flex flex-col space-y-2">
                <div className="flex justify-between items-center">
                    <span className="font-bold flex items-center space-x-2">
                        <Bot className="h-4 w-4 text-muted-foreground" />
                        <span>{model.name}</span>
                    </span>
                    <Badge variant={model.is_active ? 'default' : 'secondary'}>
                        {model.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                </div>
                <div className="text-sm">Model: {model.default_model || 'Not Set'}</div>
                <div className="text-sm border-t pt-2 border-border mt-2">
                    Key status:{' '}
                    <Badge variant={model.has_key ? 'default' : 'destructive'} className={model.has_key ? 'ml-1 bg-green-600 hover:bg-green-700' : 'ml-1'}>
                        {model.has_key ? 'Configured' : 'Missing'}
                    </Badge>
                </div>
                {canEdit && (
                    <div className="flex justify-between items-center pt-2 border-t mt-2">
                        <Button variant="ghost" size="sm" className="text-destructive h-8 px-2" onClick={() => handleResetUsage(model.id)}>
                            <RotateCcw className="mr-2 h-4 w-4" />
                            Reset Usage
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => setEditingHub(model)}>
                            <Edit className="mr-2 h-4 w-4" />
                            Edit
                        </Button>
                    </div>
                )}
            </CardContent>
        </Card>
    );

    return (
        <AuthenticatedLayout header="AI Integrations">
            <Head title="AI Integrations" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center mb-6">
                        <h1 className="text-2xl font-bold tracking-tight">AI Integrations</h1>
                        <p className="text-sm text-muted-foreground">Manage AI Models and API Keys globally.</p>
                    </div>

                    <div className="mb-8">
                        <h2 className="text-lg font-semibold mb-4">Active Provider</h2>
                        <div className="bg-card text-card-foreground shadow-xs sm:rounded-lg border-2 border-primary/20 overflow-hidden">
                            <div className="p-6">
                                {integrations.filter(i => i.is_active).length > 0 ? (
                                    <DataTable
                                        columns={columns.filter(c => c.id !== 'is_active')}
                                        data={integrations.filter(i => i.is_active)}
                                    />
                                ) : (
                                    <div className="text-center py-6 text-muted-foreground">
                                        No active AI provider configured. Please select and configure one below.
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 className="text-lg font-semibold mb-4">Available Providers</h2>
                        <div className="bg-card text-card-foreground shadow-xs sm:rounded-lg opacity-80 hover:opacity-100 transition-opacity overflow-hidden">
                            <div className="p-6">
                                <DataTable
                                    columns={columns.filter(c => c.id !== 'is_active')}
                                    data={integrations.filter(i => !i.is_active)}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Sheet open={!!editingHub} onOpenChange={(open) => !open && setEditingHub(null)}>
                <SheetContent className="sm:max-w-md overflow-y-auto">
                    <SheetHeader>
                        <SheetTitle>Configure {editingHub?.name}</SheetTitle>
                        <SheetDescription>
                            Update the API key and default model for this AI provider.
                        </SheetDescription>
                    </SheetHeader>
                    {editingHub && (
                        <form onSubmit={handleSave} className="space-y-6 flex flex-col mt-6">
                            <div className="space-y-4 flex-1">
                                <div className="flex items-center justify-between rounded-lg border p-3 shadow-xs">
                                    <div className="space-y-0.5">
                                        <Label>Active Provider</Label>
                                        <p className="text-sm text-muted-foreground w-[180px]">
                                            Set this provider as the active AI engine. Deactivates others.
                                        </p>
                                    </div>
                                    <Switch
                                        checked={data.is_active}
                                        onCheckedChange={(checked) => setData('is_active', checked)}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="api_key">API Key</Label>
                                    {editingHub.has_key && !editingKey ? (
                                        <div className="flex items-center gap-2">
                                            <div className={`flex-1 flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-mono ${data.clear_key ? 'bg-destructive/10 text-destructive border-destructive/20' : 'bg-muted text-muted-foreground'}`}>
                                                <KeyRound className="h-3.5 w-3.5 shrink-0" />
                                                <span className={data.clear_key ? "line-through" : ""}>
                                                    {data.clear_key ? "Key will be removed" : "••••••••••••••••••••••••"}
                                                </span>
                                            </div>
                                            {!data.clear_key ? (
                                                <>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => setEditingKey(true)}
                                                    >
                                                        <Pencil className="h-3.5 w-3.5 mr-1" />
                                                        Change
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                        onClick={() => setData('clear_key', true)}
                                                    >
                                                        Remove
                                                    </Button>
                                                </>
                                            ) : (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => setData('clear_key', false)}
                                                >
                                                    Undo
                                                </Button>
                                            )}
                                        </div>
                                    ) : (
                                        <>
                                            <Input
                                                id="api_key"
                                                type="text"
                                                value={data.api_key}
                                                onChange={(e) => setData('api_key', e.target.value)}
                                                placeholder={`Enter your ${editingHub.name} API Key`}
                                                className="font-mono"
                                                autoFocus={editingHub.has_key}
                                            />
                                            {editingHub.has_key && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-auto p-0 text-xs text-muted-foreground"
                                                    onClick={() => { setEditingKey(false); setData(d => ({ ...d, api_key: '', clear_key: false })); }}
                                                >
                                                    Cancel key change
                                                </Button>
                                            )}
                                        </>
                                    )}
                                    {errors.api_key && <p className="text-sm text-destructive">{errors.api_key}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="default_model">Default Model</Label>
                                    <Input
                                        id="default_model"
                                        type="text"
                                        value={data.default_model}
                                        onChange={(e) => setData('default_model', e.target.value)}
                                        placeholder="e.g. gemini-2.5-flash, gpt-4o..."
                                    />
                                    <p className="text-sm text-muted-foreground">
                                        The model used for text generation.
                                    </p>
                                    {errors.default_model && <p className="text-sm text-destructive">{errors.default_model}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="image_model">Image Model</Label>
                                    <Input
                                        id="image_model"
                                        type="text"
                                        value={data.image_model}
                                        onChange={(e) => setData('image_model', e.target.value)}
                                        placeholder="e.g. gemini-2.0-flash-preview-image-generation, dall-e-3"
                                    />
                                    <p className="text-sm text-muted-foreground">
                                        The model used for image generation. Leave blank to use the provider default.
                                    </p>
                                    {errors.image_model && <p className="text-sm text-destructive">{errors.image_model}</p>}
                                </div>
                            </div>
                            <div className="flex justify-end pt-4 border-t">
                                <Button type="button" variant="ghost" onClick={() => setEditingHub(null)} className="mr-2">Cancel</Button>
                                <Button type="submit" disabled={processing}>Save Changes</Button>
                            </div>
                        </form>
                    )}
                </SheetContent>
            </Sheet>
        </AuthenticatedLayout>
    );
}

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { FormSplitLayout, StickyFormFooter } from '@/Components/Common/FormLayouts';
import { Trash2, Plus, ArrowUp, ArrowDown } from 'lucide-react';
import { Separator } from '@/Components/ui/separator';

interface MenuSubItem {
    id: string;
    title: string;
    url: string;
    target: '_self' | '_blank';
}

interface MenuItem {
    id: string;
    title: string;
    url: string;
    target: '_self' | '_blank';
    subItems: MenuSubItem[];
}

interface MenuModel {
    id: string;
    name: string;
    slug: string;
    items: MenuItem[] | null;
    is_active: boolean;
}

interface MenuEditProps {
    auth: any;
    menu: MenuModel;
}

const SubItemRow = ({
    subItem,
    subIndex,
    onUpdate,
    onRemove,
    onMove,
    isFirst,
    isLast
}: {
    subItem: MenuSubItem,
    subIndex: number,
    onUpdate: (field: string, value: any) => void,
    onRemove: () => void,
    onMove: (direction: 'up' | 'down') => void,
    isFirst: boolean,
    isLast: boolean
}) => (
    <div className="flex flex-col relative before:absolute before:border-l-2 before:border-b-2 before:w-4 before:h-8 before:-left-6 before:-top-2 rounded-md">
        <div className="flex items-start gap-3">
            <div className="flex flex-col gap-1 pr-2 mt-1">
                <Button type="button" variant="ghost" size="icon" className="h-5 w-5" onClick={() => onMove('up')} disabled={isFirst}>
                    <ArrowUp className="h-3 w-3" />
                </Button>
                <Button type="button" variant="ghost" size="icon" className="h-5 w-5" onClick={() => onMove('down')} disabled={isLast}>
                    <ArrowDown className="h-3 w-3" />
                </Button>
            </div>
            <div className="flex-1 grid grid-cols-12 gap-3 items-end">
                <div className="col-span-4">
                    <Input value={subItem.title} onChange={(e) => onUpdate('title', e.target.value)} placeholder="Sub-item title" className="h-8 text-sm" />
                </div>
                <div className="col-span-5">
                    <Input value={subItem.url} onChange={(e) => onUpdate('url', e.target.value)} placeholder="/sub-page" className="h-8 text-sm" />
                </div>
                <div className="col-span-2 flex justify-center">
                    <Switch checked={subItem.target === '_blank'} onCheckedChange={(checked) => onUpdate('target', checked ? '_blank' : '_self')} className="scale-75 origin-center" />
                </div>
                <div className="col-span-1 flex justify-end">
                    <Button type="button" variant="ghost" size="icon" className="text-destructive hover:text-destructive h-7 w-7" onClick={onRemove}>
                        <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                </div>
            </div>
        </div>
    </div>
);

const MenuItemRow = ({
    item,
    index,
    isFirst,
    isLast,
    onUpdate,
    onRemove,
    onMove,
    onAddSub,
    onUpdateSub,
    onRemoveSub,
    onMoveSub
}: {
    item: MenuItem,
    index: number,
    isFirst: boolean,
    isLast: boolean,
    onUpdate: (field: string, value: any) => void,
    onRemove: () => void,
    onMove: (direction: 'up' | 'down') => void,
    onAddSub: () => void,
    onUpdateSub: (subIndex: number, field: string, value: any) => void,
    onRemoveSub: (subIndex: number) => void,
    onMoveSub: (subIndex: number, direction: 'up' | 'down') => void
}) => (
    <div className="border rounded-md p-4 bg-muted/20 space-y-4">
        <div className="flex items-start gap-4">
            <div className="flex flex-col gap-1 border-r pr-4 mt-1">
                <Button type="button" variant="ghost" size="icon" className="h-6 w-6" onClick={() => onMove('up')} disabled={isFirst}>
                    <ArrowUp className="h-4 w-4" />
                </Button>
                <Button type="button" variant="ghost" size="icon" className="h-6 w-6" onClick={() => onMove('down')} disabled={isLast}>
                    <ArrowDown className="h-4 w-4" />
                </Button>
            </div>
            <div className="flex-1 grid grid-cols-12 gap-3 items-end">
                <div className="col-span-4 space-y-1.5">
                    <Label className="text-xs">Title</Label>
                    <Input value={item.title} onChange={(e) => onUpdate('title', e.target.value)} placeholder="e.g. About Us" className="h-9" />
                </div>
                <div className="col-span-5 space-y-1.5">
                    <Label className="text-xs">URL or Route Slug</Label>
                    <Input value={item.url} onChange={(e) => onUpdate('url', e.target.value)} placeholder="/about or https://..." className="h-9" />
                </div>
                <div className="col-span-2 space-y-1.5 flex flex-col items-center">
                    <Label className="text-xs">Open in New Tab</Label>
                    <div className="h-9 flex items-center">
                        <Switch checked={item.target === '_blank'} onCheckedChange={(checked) => onUpdate('target', checked ? '_blank' : '_self')} />
                    </div>
                </div>
                <div className="col-span-1 justify-end flex pb-0.5">
                    <Button type="button" variant="ghost" size="icon" className="text-destructive hover:text-destructive h-8 w-8" onClick={onRemove}>
                        <Trash2 className="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>

        <div className="pl-12 space-y-3">
            {(item.subItems || []).map((subItem, subIndex) => (
                <SubItemRow
                    key={subItem.id}
                    subItem={subItem}
                    subIndex={subIndex}
                    isFirst={subIndex === 0}
                    isLast={subIndex === (item.subItems || []).length - 1}
                    onUpdate={(field, value) => onUpdateSub(subIndex, field, value)}
                    onRemove={() => onRemoveSub(subIndex)}
                    onMove={(dir) => onMoveSub(subIndex, dir)}
                />
            ))}
            <Button type="button" variant="outline" size="sm" onClick={onAddSub} className="h-7 text-xs shadow-none border-dashed bg-transparent">
                <Plus className="mr-1 h-3 w-3" /> Add Dropdown Link
            </Button>
        </div>
    </div>
);

export default function Edit({ menu }: MenuEditProps) {
    const { data, setData, put, processing, errors, isDirty } = useForm({
        name: menu.name || '',
        slug: menu.slug || '',
        items: (menu.items || []) as MenuItem[],
        is_active: menu.is_active ?? true,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('menus.update', menu.id));
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this menu?')) {
            router.delete(route('menus.destroy', menu.id));
        }
    };

    const generateId = () => Math.random().toString(36).substr(2, 9);

    const addItem = () => {
        setData('items', [...data.items, { id: generateId(), title: '', url: '', target: '_self', subItems: [] }]);
    };

    const updateItem = (index: number, field: string, value: any) => {
        setData('items', data.items.map((item, i) =>
            i === index ? { ...item, [field]: value } : item
        ));
    };

    const removeItem = (index: number) => {
        setData('items', data.items.filter((_, i) => i !== index));
    };

    const moveItem = (index: number, direction: 'up' | 'down') => {
        if (direction === 'up' && index === 0) return;
        if (direction === 'down' && index === data.items.length - 1) return;

        const newItems = [...data.items];
        const targetIndex = direction === 'up' ? index - 1 : index + 1;
        const temp = newItems[targetIndex];
        newItems[targetIndex] = newItems[index];
        newItems[index] = temp;
        setData('items', newItems);
    };

    // Sub-items management
    const addSubItem = (parentIndex: number) => {
        setData('items', data.items.map((item, i) => {
            if (i !== parentIndex) return item;
            return {
                ...item,
                subItems: [...(item.subItems || []), { id: generateId(), title: '', url: '', target: '_self' }]
            };
        }));
    };

    const updateSubItem = (parentIndex: number, subIndex: number, field: string, value: any) => {
        setData('items', data.items.map((item, i) => {
            if (i !== parentIndex) return item;
            return {
                ...item,
                subItems: (item.subItems || []).map((sub, si) =>
                    si === subIndex ? { ...sub, [field]: value } : sub
                )
            };
        }));
    };

    const removeSubItem = (parentIndex: number, subIndex: number) => {
        setData('items', data.items.map((item, i) => {
            if (i !== parentIndex) return item;
            return {
                ...item,
                subItems: (item.subItems || []).filter((_, si) => si !== subIndex)
            };
        }));
    };

    const moveSubItem = (parentIndex: number, subIndex: number, direction: 'up' | 'down') => {
        const parent = data.items[parentIndex];
        const subItems = [...(parent.subItems || [])];
        if (direction === 'up' && subIndex === 0) return;
        if (direction === 'down' && subIndex === subItems.length - 1) return;

        const targetIndex = direction === 'up' ? subIndex - 1 : subIndex + 1;
        const temp = subItems[targetIndex];
        subItems[targetIndex] = subItems[subIndex];
        subItems[subIndex] = temp;

        setData('items', data.items.map((item, i) =>
            i === parentIndex ? { ...item, subItems } : item
        ));
    };

    return (
        <AuthenticatedLayout header="Edit Menu">
            <Head title={`Edit Menu: ${data.name}`} />

            <form onSubmit={submit}>
                <div className="flex flex-col gap-6">
                    <div className="flex items-center justify-between">
                        <h1 className="text-2xl font-bold tracking-tight">Edit Menu</h1>
                    </div>

                    <FormSplitLayout
                        sidebar={
                            <div className="space-y-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Status</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="flex items-center space-x-2 border p-3 rounded-md">
                                            <Switch
                                                id="is_active"
                                                checked={data.is_active}
                                                onCheckedChange={(checked) => setData('is_active', checked)}
                                            />
                                            <Label htmlFor="is_active" className="cursor-pointer flex-1">
                                                Active
                                            </Label>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        }
                    >
                        <div className="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Menu Details</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="name">Name</Label>
                                            <Input
                                                id="name"
                                                value={data.name}
                                                onChange={(e) => setData('name', e.target.value)}
                                                required
                                            />
                                            {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="slug">Slug</Label>
                                            <Input
                                                id="slug"
                                                value={data.slug}
                                                onChange={(e) => setData('slug', e.target.value)}
                                                required
                                            />
                                            {errors.slug && <p className="text-sm text-destructive">{errors.slug}</p>}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="flex flex-row items-center justify-between">
                                    <div>
                                        <CardTitle>Menu Items</CardTitle>
                                        <CardDescription>Build the navigation tree.</CardDescription>
                                    </div>
                                    <Button type="button" onClick={addItem} size="sm">
                                        <Plus className="mr-2 h-4 w-4" /> Add Item
                                    </Button>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {data.items.length === 0 && (
                                        <div className="text-center py-6 border-2 border-dashed rounded-md text-muted-foreground">
                                            No items yet.
                                        </div>
                                    )}

                                    {data.items.map((item, index) => (
                                        <MenuItemRow
                                            key={item.id}
                                            item={item}
                                            index={index}
                                            isFirst={index === 0}
                                            isLast={index === data.items.length - 1}
                                            onUpdate={(field, value) => updateItem(index, field, value)}
                                            onRemove={() => removeItem(index)}
                                            onMove={(dir) => moveItem(index, dir)}
                                            onAddSub={() => addSubItem(index)}
                                            onUpdateSub={(subIndex, field, value) => updateSubItem(index, subIndex, field, value)}
                                            onRemoveSub={(subIndex) => removeSubItem(index, subIndex)}
                                            onMoveSub={(subIndex, dir) => moveSubItem(index, subIndex, dir)}
                                        />
                                    ))}
                                </CardContent>
                            </Card>
                        </div>
                    </FormSplitLayout>
                </div>

                <StickyFormFooter
                    isSaving={processing}
                    isDirty={isDirty}
                    onSave={submit as any}
                    canDelete={true}
                    onDelete={handleDelete}
                />
            </form>
        </AuthenticatedLayout>
    );
}

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { FormEventHandler, useState, useEffect } from 'react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Switch } from '@/Components/ui/switch';
/* @ts-ignore */
import Editor from '@/Components/Editor';
import { FormSplitLayout, StickyFormFooter } from '@/Components/Common/FormLayouts';
import { SlugInput } from '@/Components/SlugInput';
import ImagePicker from '@/Components/ImagePicker';
import { SerpPreview } from '@/Components/SerpPreview';
import { Separator } from '@/Components/ui/separator';
import { CharacterCounter } from '@/Components/CharacterCounter';
import { UrlPreview } from '@/Components/UrlPreview';
import { Sparkles, Loader2, ArrowRight, Maximize2, Minimize2 } from 'lucide-react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";

interface PageModel {
    id: string;
    title: string;
    slug: string; // Ensure slug is in model
    content: string;
    status: string;
    seo_title: string;
    seo_description: string;
    featured_image?: string;
    featured_images?: string[];
}

interface RedirectModel {
    id: string;
    from_path: string;
    to_path: string;
    created_at: string;
}

interface PageEditProps {
    auth: any;
    page: PageModel;
    redirects: RedirectModel[];
}

export default function Edit({ auth, page, redirects }: PageEditProps) {
    const [isExpanded, setIsExpanded] = useState(() => {
        const saved = localStorage.getItem('page_edit_expand_sidebar');
        return saved ? JSON.parse(saved) : false;
    });

    useEffect(() => {
        localStorage.setItem('page_edit_expand_sidebar', JSON.stringify(isExpanded));
    }, [isExpanded]);

    const { data, setData, put, processing, errors, isDirty } = useForm<{
        title: string;
        slug: string;
        content: string;
        status: string;
        seo_title: string;
        seo_description: string;
        featured_image: string;
        featured_images: string[];
    }>({
        title: page.title || '',
        slug: page.slug || '',
        content: page.content || '',
        status: page.status || 'draft',
        seo_title: page.seo_title || '',
        seo_description: page.seo_description || '',
        featured_image: page.featured_image || '',
        featured_images: page.featured_images || [] as string[],
    });

    const [lockedSlug, setLockedSlug] = useState(true);
    const [isGeneratingAI, setIsGeneratingAI] = useState(false);
    const [lastSaved, setLastSaved] = useState<Date | null>(null);
    const [editorHeight, setEditorHeight] = useState(() => {
        const saved = localStorage.getItem('page_editor_height');
        return saved ? parseInt(saved, 10) : 400;
    });

    useEffect(() => {
        localStorage.setItem('page_editor_height', editorHeight.toString());
    }, [editorHeight]);

    // Keyboard shortcuts
    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            // Cmd+S or Ctrl+S to save
            if ((e.metaKey || e.ctrlKey) && e.key === 's') {
                e.preventDefault();
                if (!processing && isDirty) {
                    submit(e as any);
                }
            }
            // Cmd+Shift+P or Ctrl+Shift+P to publish
            if ((e.metaKey || e.ctrlKey) && e.shiftKey && e.key === 'p') {
                e.preventDefault();
                if (data.status !== 'published') {
                    setData('status', 'published');
                }
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [processing, isDirty, data.status]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('pages.update', page.id), {
            onSuccess: () => {
                setLastSaved(new Date());
            },
        });
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this page?')) {
            router.delete(route('pages.destroy', page.id));
        }
    };

    // Auto-generate AI SEO with loading state
    const generateSEO = () => {
        if (!data.title || !data.content) {
            alert('Please enter a title and content first.');
            return;
        }
        setIsGeneratingAI(true);
        // @ts-ignore
        axios.post(route('ai.seo'), {
            title: data.title,
            content: data.content
        }).then((response: any) => {
            setData(data => ({
                ...data,
                seo_title: response.data.seo_title,
                seo_description: response.data.seo_description
            }));
        }).catch((error: any) => {
            console.error(error);
            alert('Failed to generate SEO metadata.');
        }).finally(() => {
            setIsGeneratingAI(false);
        });
    };

    // Format last saved time
    const getLastSavedText = () => {
        if (!lastSaved) return null;
        const seconds = Math.floor((new Date().getTime() - lastSaved.getTime()) / 1000);
        if (seconds < 60) return 'Just now';
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        const hours = Math.floor(minutes / 60);
        return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    };

    return (
        <AuthenticatedLayout header="Edit Page">
            <Head title={`Edit: ${data.title}`} />

            <form onSubmit={submit}>
                <div className="flex flex-col gap-6">
                    {/* Header with Status Badge */}
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold tracking-tight">Edit Page</h1>
                            <Badge variant={data.status === 'published' ? 'default' : 'secondary'}>
                                {data.status === 'published' ? 'Published' : 'Draft'}
                            </Badge>
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => setIsExpanded(!isExpanded)}
                            className="text-muted-foreground hover:text-foreground"
                        >
                            {isExpanded ? (
                                <>
                                    <Minimize2 className="h-4 w-4 mr-2" />
                                    Collapse Sidebar
                                </>
                            ) : (
                                <>
                                    <Maximize2 className="h-4 w-4 mr-2" />
                                    Expand Content
                                </>
                            )}
                        </Button>
                    </div>

                    <FormSplitLayout
                        isExpanded={isExpanded}
                        sidebar={
                            <div className="space-y-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Publishing</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="flex items-center space-x-2 border p-3 rounded-md">
                                            <Switch
                                                id="status"
                                                checked={data.status === 'published'}
                                                onCheckedChange={(checked) => {
                                                    const newStatus = checked ? 'published' : 'draft';
                                                    setData('status', newStatus);
                                                    // Autosave status change
                                                    router.put(route('pages.update', page.id), {
                                                        ...data,
                                                        status: newStatus,
                                                        stay: 1
                                                    }, { preserveScroll: true });
                                                }}
                                            />
                                            <Label htmlFor="status" className="cursor-pointer flex-1">
                                                {data.status === 'published' ? 'Published' : 'Draft'}
                                            </Label>
                                        </div>
                                    </CardContent>
                                </Card>


                                <Card>
                                    <CardHeader>
                                        <CardTitle>SEO Settings</CardTitle>
                                        <CardDescription>Search Engine Optimization</CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="seo_title">SEO Title</Label>
                                            <Input
                                                id="seo_title"
                                                value={data.seo_title}
                                                onChange={(e) => setData('seo_title', e.target.value)}
                                                placeholder={data.title || "Page Title"}
                                            />
                                            <CharacterCounter
                                                current={data.seo_title.length}
                                                ideal={{ min: 50, max: 60 }}
                                                max={70}
                                            />
                                            {errors.seo_title && <p className="text-sm text-destructive">{errors.seo_title}</p>}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="seo_description">Meta Description</Label>
                                            <Textarea
                                                id="seo_description"
                                                value={data.seo_description}
                                                onChange={(e) => setData('seo_description', e.target.value)}
                                                placeholder="Brief description for search results"
                                                className="h-24 resize-none"
                                            />
                                            <CharacterCounter
                                                current={data.seo_description.length}
                                                ideal={{ min: 150, max: 160 }}
                                                max={200}
                                            />
                                            {errors.seo_description && <p className="text-sm text-destructive">{errors.seo_description}</p>}
                                        </div>

                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            className="w-full"
                                            onClick={generateSEO}
                                            disabled={isGeneratingAI || !data.title || !data.content}
                                        >
                                            {isGeneratingAI ? (
                                                <>
                                                    <Loader2 className="mr-2 h-3 w-3 animate-spin" />
                                                    Generating...
                                                </>
                                            ) : (
                                                <>
                                                    <Sparkles className="mr-2 h-3 w-3" /> Generate AI Metadata
                                                </>
                                            )}
                                        </Button>

                                        <Separator className="my-2" />

                                        <div className="space-y-2">
                                            <Label>Preview</Label>
                                            <div className="rounded-md border p-3 bg-muted/20">
                                                <SerpPreview
                                                    title={data.seo_title || data.title || 'Page Title'}
                                                    description={data.seo_description || 'Page description will appear here...'}
                                                    slug={data.slug || 'page-slug'}
                                                />
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                                {redirects && redirects.length > 0 && (
                                    <Card>
                                        <CardHeader>
                                            <CardTitle>Redirects</CardTitle>
                                            <CardDescription>Old URLs pointing to this page</CardDescription>
                                        </CardHeader>
                                        <CardContent>
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>From Path</TableHead>
                                                        <TableHead className="text-right">Date</TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {redirects.map((redirect) => (
                                                        <TableRow key={redirect.id}>
                                                            <TableCell className="font-mono text-xs">
                                                                /{redirect.from_path}
                                                                <ArrowRight className="inline-block mx-1 h-3 w-3 text-muted-foreground" />
                                                                /{redirect.to_path}
                                                            </TableCell>
                                                            <TableCell className="text-right text-xs text-muted-foreground">
                                                                {new Date(redirect.created_at).toLocaleDateString()}
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </CardContent>
                                    </Card>
                                )}
                            </div>
                        }
                    >
                        <div className="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Page Content</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="title">Page Title</Label>
                                        <Input
                                            id="title"
                                            value={data.title}
                                            onChange={(e) => setData('title', e.target.value)}
                                            className="text-lg font-medium"
                                            placeholder="Enter page title"
                                            required
                                        />
                                        {errors.title && <p className="text-sm text-destructive">{errors.title}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="slug">URL Slug</Label>
                                        <SlugInput
                                            value={data.slug}
                                            sourceValue={data.title}
                                            isEditing={true}
                                            onChange={(slug: string) => setData('slug', slug)}
                                        />
                                        <UrlPreview slug={data.slug} />
                                        {/* @ts-ignore */}
                                        {errors.slug && <p className="text-sm text-destructive">{errors.slug}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <div className="flex items-center justify-between">
                                            <Label>Content</Label>
                                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                <button
                                                    type="button"
                                                    onClick={() => setEditorHeight(Math.max(300, editorHeight - 100))}
                                                    className="hover:text-foreground"
                                                >
                                                    -
                                                </button>
                                                <span>{editorHeight}px</span>
                                                <button
                                                    type="button"
                                                    onClick={() => setEditorHeight(Math.min(800, editorHeight + 100))}
                                                    className="hover:text-foreground"
                                                >
                                                    +
                                                </button>
                                            </div>
                                        </div>
                                        <div className="border rounded-md" style={{ minHeight: `${editorHeight}px` }}>
                                            <Editor
                                                value={data.content}
                                                onChange={(data: string) => setData('content', data)}
                                                height={editorHeight}
                                            />
                                        </div>
                                        {errors.content && <p className="text-sm text-destructive">{errors.content}</p>}
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Banner Gallery</CardTitle>
                                    <CardDescription>Upload multiple images for this page gallery</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <ImagePicker
                                        value={data.featured_images}
                                        multiple={true}
                                        onChange={(urls) => setData('featured_images', urls as string[])}
                                    />
                                    {data.featured_images && data.featured_images.length > 0 && (
                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                                            {data.featured_images.map((url, index) => (
                                                <div key={index} className="aspect-video relative group rounded-md overflow-hidden border">
                                                    <img
                                                        src={url}
                                                        alt={`Gallery ${index + 1}`}
                                                        className="w-full h-full object-cover"
                                                    />
                                                    <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                                        <Button
                                                            type="button"
                                                            variant="destructive"
                                                            size="sm"
                                                            onClick={() => {
                                                                const newImages = [...data.featured_images];
                                                                newImages.splice(index, 1);
                                                                setData('featured_images', newImages);
                                                            }}
                                                        >
                                                            Remove
                                                        </Button>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                    {errors.featured_images && <p className="text-sm text-destructive mt-1">{errors.featured_images}</p>}
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
                    lastSaved={getLastSavedText()}
                />
            </form >
        </AuthenticatedLayout >
    );
}

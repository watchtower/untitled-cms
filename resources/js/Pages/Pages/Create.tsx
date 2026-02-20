import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useState, useEffect } from 'react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
/* @ts-ignore */
import Editor from '@/Components/Editor';
import { FormSplitLayout, StickyFormFooter } from '@/Components/Common/FormLayouts';
import { SlugInput } from '@/Components/SlugInput';
import { SerpPreview } from '@/Components/SerpPreview';
import { Separator } from '@/Components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { Sparkles } from 'lucide-react';

export default function Create({ auth }: any) {
    const { data, setData, post, processing, errors, isDirty } = useForm({
        title: '',
        slug: '', // Add slug field
        content: '',
        status: 'draft',
        seo_title: '',
        seo_description: '',
    });

    const [lockedSlug, setLockedSlug] = useState(true);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('pages.store'));
    };

    // Auto-generate AI SEO
    const generateSEO = () => {
        if (!data.title || !data.content) {
            alert('Please enter a title and content first.');
            return;
        }
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
        });
    }

    return (
        <AuthenticatedLayout header="Create Page">
            <Head title="Create Page" />

            <form onSubmit={submit}>
                <div className="flex flex-col gap-6">
                    <div className="flex items-center justify-between">
                        <h1 className="text-2xl font-bold tracking-tight">Create Page</h1>
                    </div>

                    <FormSplitLayout
                        sidebar={
                            <div className="space-y-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Publishing</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="space-y-2">
                                            <Label>Status</Label>
                                            <Select
                                                value={data.status}
                                                onValueChange={(value) => setData('status', value)}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select status" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="draft">Draft</SelectItem>
                                                    <SelectItem value="published">Published</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            {errors.status && <p className="text-sm text-destructive">{errors.status}</p>}
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
                                            {errors.seo_description && <p className="text-sm text-destructive">{errors.seo_description}</p>}
                                        </div>

                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            className="w-full"
                                            onClick={generateSEO}
                                        >
                                            <Sparkles className="mr-2 h-3 w-3" /> Generate AI Metadata
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
                                            autoFocus
                                        />
                                        {errors.title && <p className="text-sm text-destructive">{errors.title}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="slug">URL Slug</Label>
                                        <SlugInput
                                            value={data.slug}
                                            sourceValue={data.title}
                                            isEditing={false}
                                            onChange={(slug: string) => setData('slug', slug)}
                                        />
                                        {/* @ts-ignore */}
                                        {errors.slug && <p className="text-sm text-destructive">{errors.slug}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label>Content</Label>
                                        <div className="min-h-[400px] border rounded-md">
                                            <Editor
                                                value={data.content}
                                                onChange={(data: string) => setData('content', data)}
                                            />
                                        </div>
                                        {errors.content && <p className="text-sm text-destructive">{errors.content}</p>}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </FormSplitLayout>
                </div>

                <StickyFormFooter
                    isSaving={processing}
                    isDirty={isDirty}
                    onSave={submit as any}
                />
            </form>
        </AuthenticatedLayout>
    );
}

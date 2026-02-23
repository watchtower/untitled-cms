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
import { AiInput } from '@/Components/Ai/AiInput';
import { AiTextarea } from '@/Components/Ai/AiTextarea';
import { CharacterCounter } from '@/Components/CharacterCounter';
import { AiAssistButton } from '@/Components/Ai/AiAssistButton';

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
                                            <AiInput
                                                id="seo_title"
                                                value={data.seo_title}
                                                onChange={(e) => setData('seo_title', e.target.value)}
                                                onGeneration={(text) => setData('seo_title', text)}
                                                placeholder={data.title || "Page Title"}
                                                aiPromptLabel="What should the SEO title emphasize?"
                                            />
                                            <CharacterCounter
                                                current={data.seo_title.length}
                                                ideal={{ min: 50, max: 60 }}
                                                max={70}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="seo_description">Meta Description</Label>
                                            <AiTextarea
                                                id="seo_description"
                                                value={data.seo_description}
                                                onChange={(e) => setData('seo_description', e.target.value)}
                                                onGeneration={(text) => setData('seo_description', text)}
                                                placeholder="Brief description for search results"
                                                aiPromptLabel="What should the meta description highlight?"
                                                className="h-24 resize-none"
                                            />
                                            <CharacterCounter
                                                current={data.seo_description.length}
                                                ideal={{ min: 140, max: 160 }}
                                                max={200}
                                            />
                                        </div>

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
                                        <AiInput
                                            id="title"
                                            value={data.title}
                                            onChange={(e) => setData('title', e.target.value)}
                                            onGeneration={(text) => setData('title', text)}
                                            className="text-lg font-medium"
                                            placeholder="Enter page title"
                                            aiPromptLabel="What should the page title be about?"
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
                                        <div className="flex items-center justify-between pb-1">
                                            <Label>Content</Label>
                                            <AiAssistButton
                                                onGeneration={(text) => {
                                                    // Try to insert directly into the active TinyMCE instance if available
                                                    if (typeof window !== 'undefined' && window.tinymce && window.tinymce.activeEditor) {
                                                        const editor = window.tinymce.activeEditor;
                                                        const spacing = editor.getContent() ? '<br><br>' : '';
                                                        editor.execCommand('mceInsertContent', false, spacing + text);
                                                    } else {
                                                        // Fallback to React state append
                                                        const spacing = data.content ? '<br><br>' : '';
                                                        setData('content', data.content + spacing + text);
                                                    }
                                                }}
                                                aiPromptPlaceholder="e.g. Write a detailed introduction about..."
                                            />
                                        </div>
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

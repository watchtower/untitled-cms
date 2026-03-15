import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Checkbox } from '@/Components/ui/checkbox';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Separator } from '@/Components/ui/separator';
import { FormSplitLayout, StickyFormFooter } from '@/Components/Common/FormLayouts';
import { Trash2, Plus, GripVertical, Image as ImageIcon } from 'lucide-react';
/* @ts-ignore */
import ImagePicker from '@/Components/ImagePicker';
import { AiInput } from '@/Components/Ai/AiInput';
import { AiAssistButton } from '@/Components/Ai/AiAssistButton';
import { toast } from 'sonner';

export default function Create({ auth }: any) {
    const { data, setData, post, processing, errors, isDirty } = useForm({
        title: '',
        slides: [] as {
            image: string;
            url: string;
            sequence: number;
            title: string;
            subtitle: string;
            caption: string;
        }[],
        is_active: true,
        start_at: '',
        end_at: ''
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('admin.banners.store'));
    };

    const addSlide = () => {
        setData('slides', [...data.slides, {
            image: '',
            url: '',
            sequence: data.slides.length + 1,
            title: '',
            subtitle: '',
            caption: ''
        }]);
    };

    const removeSlide = (index: number) => {
        const newSlides = data.slides.filter((_, i) => i !== index);
        setData('slides', newSlides);
    };

    const updateSlide = (index: number, field: string, value: any) => {
        const newSlides = [...data.slides];
        // @ts-ignore
        newSlides[index][field] = value;
        setData('slides', newSlides);
    };

    return (
        <AuthenticatedLayout header="Create Banner">
            <Head title="Create Banner" />

            <form onSubmit={submit}>
                <div className="flex flex-col gap-6">
                    <div className="flex items-center justify-between">
                        <h1 className="text-2xl font-bold tracking-tight">Create Banner</h1>
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
                                            <Checkbox
                                                id="is_active"
                                                checked={data.is_active}
                                                onCheckedChange={(checked) => setData('is_active', !!checked)}
                                            />
                                            <Label htmlFor="is_active" className="cursor-pointer flex-1">
                                                Active
                                            </Label>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Schedule</CardTitle>
                                        <CardDescription>Optional visibility dates</CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="start_at">Start Date</Label>
                                            <Input
                                                id="start_at"
                                                type="date"
                                                value={data.start_at}
                                                onChange={(e) => setData('start_at', e.target.value)}
                                            />
                                            {errors.start_at && <p className="text-sm text-destructive">{errors.start_at}</p>}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="end_at">End Date</Label>
                                            <Input
                                                id="end_at"
                                                type="date"
                                                value={data.end_at}
                                                onChange={(e) => setData('end_at', e.target.value)}
                                            />
                                            {errors.end_at && <p className="text-sm text-destructive">{errors.end_at}</p>}
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        }
                    >
                        <div className="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Banner Details</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="title">Name</Label>
                                        <Input
                                            id="title"
                                            value={data.title}
                                            onChange={(e) => setData('title', e.target.value)}
                                            placeholder="Enter banner name (internal use)"
                                            required
                                            autoFocus
                                        />
                                        {errors.title && <p className="text-sm text-destructive">{errors.title}</p>}
                                    </div>
                                </CardContent>
                            </Card>

                            <div className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-lg font-medium">Slides</h3>
                                    <Button type="button" onClick={addSlide} size="sm" variant="secondary">
                                        <Plus className="mr-2 h-4 w-4" /> Add Slide
                                    </Button>
                                </div>

                                {data.slides.length === 0 && (
                                    <div className="text-center py-10 border-2 border-dashed rounded-lg text-muted-foreground bg-muted/20">
                                        <ImageIcon className="h-10 w-10 mx-auto mb-2 opacity-50" />
                                        <p>No slides added yet.</p>
                                        <Button type="button" variant="link" onClick={addSlide}>Add your first slide</Button>
                                    </div>
                                )}

                                {data.slides.map((slide, index) => (
                                    <Card key={index} className="relative overflow-hidden">
                                        <div className="absolute left-0 top-0 bottom-0 w-1 bg-primary/20" />
                                        <CardHeader className="pb-3 bg-muted/40 flex flex-row items-center justify-between space-y-0">
                                            <div className="flex items-center gap-4">
                                                <CardTitle className="text-sm font-medium flex items-center gap-2">
                                                    <div className="bg-background border rounded-full w-6 h-6 flex items-center justify-center text-xs text-muted-foreground">
                                                        {index + 1}
                                                    </div>
                                                    Slide #{index + 1}
                                                </CardTitle>
                                                <AiAssistButton
                                                    buttonText="Auto-Generate Content"
                                                    size="sm"
                                                    variant="secondary"
                                                    className="h-7 text-xs"
                                                    aiPromptPlaceholder="e.g. A summer sale on shoes..."
                                                    systemInstruction='You are an expert copywriter. Generate a banner slide title, subtitle, and short caption. Output MUST be strictly valid JSON only, example: {"title": "...", "subtitle": "...", "caption": "..."}. Do NOT include markdown fences or any other text.'
                                                    onGeneration={(text) => {
                                                        try {
                                                            const jsonStr = text.replace(/```(?:json)?|```/g, '').trim();
                                                            const parsed = JSON.parse(jsonStr);
                                                            if (parsed.title) updateSlide(index, 'title', parsed.title);
                                                            if (parsed.subtitle) updateSlide(index, 'subtitle', parsed.subtitle);
                                                            if (parsed.caption) updateSlide(index, 'caption', parsed.caption);
                                                            toast.success('Banner content generated!');
                                                        } catch (e) {
                                                            toast.error(`Failed to parse AI response: ${text.substring(0, 60)}`);
                                                        }
                                                    }}
                                                />
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                className="text-muted-foreground hover:text-destructive h-8 w-8 p-0"
                                                onClick={() => removeSlide(index)}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </CardHeader>
                                        <CardContent className="p-4 space-y-4">
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div className="space-y-2">
                                                    <Label>Image</Label>
                                                    <ImagePicker
                                                        value={slide.image}
                                                        onChange={(url: string | string[]) => updateSlide(index, 'image', url as string)}
                                                    />
                                                </div>
                                                <div className="space-y-4">
                                                    <div className="space-y-2">
                                                        <Label>Target URL</Label>
                                                        <Input
                                                            value={slide.url}
                                                            onChange={(e) => updateSlide(index, 'url', e.target.value)}
                                                            placeholder="https://..."
                                                        />
                                                    </div>
                                                    <div className="space-y-2">
                                                        <Label>Sequence Order</Label>
                                                        <Input
                                                            type="number"
                                                            value={slide.sequence}
                                                            onChange={(e) => updateSlide(index, 'sequence', parseInt(e.target.value))}
                                                        />
                                                    </div>
                                                </div>
                                            </div>

                                            <Separator />

                                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div className="space-y-2">
                                                    <Label>Title (Overlay)</Label>
                                                    <AiInput
                                                        value={slide.title}
                                                        onChange={(e) => updateSlide(index, 'title', e.target.value)}
                                                        onGeneration={(text) => updateSlide(index, 'title', text)}
                                                        placeholder="Slide Title (generated via AI)"
                                                        aiPromptLabel="What should this title be about?"
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label>Subtitle</Label>
                                                    <AiInput
                                                        value={slide.subtitle}
                                                        onChange={(e) => updateSlide(index, 'subtitle', e.target.value)}
                                                        onGeneration={(text) => updateSlide(index, 'subtitle', text)}
                                                        placeholder="Slide Subtitle"
                                                        aiPromptLabel="What should the subtitle emphasize?"
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label>Caption</Label>
                                                    <AiInput
                                                        value={slide.caption}
                                                        onChange={(e) => updateSlide(index, 'caption', e.target.value)}
                                                        onGeneration={(text) => updateSlide(index, 'caption', text)}
                                                        placeholder="Small text"
                                                        aiPromptLabel="Write a short call to action caption."
                                                    />
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
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

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Separator } from '@/Components/ui/separator';
import { FormSplitLayout, StickyFormFooter } from '@/Components/Common/FormLayouts';
import { Trash2, Plus, GripVertical, Image as ImageIcon, List, Presentation, ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
/* @ts-ignore */
import ImagePicker from '@/Components/ImagePicker';
import { AiInput } from '@/Components/Ai/AiInput';
import { AiAssistButton } from '@/Components/Ai/AiAssistButton';
import { toast } from 'sonner';

interface BannerModel {
    id: string;
    title: string;
    slides: {
        image: string;
        url: string;
        sequence: number;
        title: string;
        subtitle: string;
        caption: string;
    }[];
    is_active: boolean;
    start_at: string;
    end_at: string;
}

interface BannerEditProps {
    auth: any;
    banner: BannerModel;
}

export default function Edit({ auth, banner }: BannerEditProps) {
    // Load view mode from localStorage, default to 'list'
    const [viewMode, setViewMode] = useState<'list' | 'carousel'>(() => {
        const saved = localStorage.getItem('banner-view-mode');
        return (saved === 'carousel' || saved === 'list') ? saved : 'list';
    });
    const [activeSlideIndex, setActiveSlideIndex] = useState(0);

    // Save view mode to localStorage when it changes
    const handleViewModeChange = (mode: 'list' | 'carousel') => {
        setViewMode(mode);
        localStorage.setItem('banner-view-mode', mode);
    };

    const { data, setData, put, processing, errors, isDirty } = useForm({
        title: banner.title || '',
        slides: (banner.slides || []) as {
            image: string;
            url: string;
            sequence: number;
            title: string;
            subtitle: string;
            caption: string;
        }[],
        is_active: banner.is_active ?? true,
        start_at: banner.start_at ? new Date(banner.start_at).toISOString().split('T')[0] : '',
        end_at: banner.end_at ? new Date(banner.end_at).toISOString().split('T')[0] : '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('banners.update', banner.id));
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this banner?')) {
            router.delete(route('banners.destroy', banner.id));
        }
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
        newSlides[index] = { ...newSlides[index], [field]: value };
        setData('slides', newSlides);
    };

    return (
        <AuthenticatedLayout header="Edit Banner">
            <Head title={`Edit Banner: ${data.title}`} />

            <form onSubmit={submit}>
                <div className="flex flex-col gap-6">
                    <div className="flex items-center justify-between">
                        <h1 className="text-2xl font-bold tracking-tight">Edit Banner</h1>
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
                                                onCheckedChange={(checked) => {
                                                    setData('is_active', checked);
                                                    router.put(route('banners.update', banner.id), {
                                                        ...data,
                                                        is_active: checked,
                                                        stay: 1
                                                    }, { preserveScroll: true });
                                                }}
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
                                        />
                                        {errors.title && <p className="text-sm text-destructive">{errors.title}</p>}
                                    </div>
                                </CardContent>
                            </Card>

                            <div className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <h3 className="text-lg font-medium">Slides</h3>
                                        <div className="flex items-center border rounded-md">
                                            <Button
                                                type="button"
                                                variant={viewMode === 'list' ? 'secondary' : 'ghost'}
                                                size="sm"
                                                className="h-8 px-2"
                                                onClick={() => handleViewModeChange('list')}
                                            >
                                                <List className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                type="button"
                                                variant={viewMode === 'carousel' ? 'secondary' : 'ghost'}
                                                size="sm"
                                                className="h-8 px-2"
                                                onClick={() => handleViewModeChange('carousel')}
                                            >
                                                <Presentation className="h-4 w-4" />
                                            </Button>
                                        </div>
                                        {/* Numbered Indicators - Carousel Mode Only */}
                                        {viewMode === 'carousel' && data.slides.length > 0 && (
                                            <div className="flex items-center gap-2">
                                                {/* Previous Button */}
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    className="h-8 w-8 p-0"
                                                    onClick={() => setActiveSlideIndex(Math.max(0, activeSlideIndex - 1))}
                                                    disabled={activeSlideIndex === 0}
                                                >
                                                    <ChevronLeft className="h-4 w-4" />
                                                </Button>

                                                {/* Numbered Circles */}
                                                {data.slides.map((_, index) => (
                                                    <button
                                                        key={index}
                                                        type="button"
                                                        onClick={() => setActiveSlideIndex(index)}
                                                        className={cn(
                                                            "w-8 h-8 rounded-full border-2 flex items-center justify-center text-xs font-medium transition-all",
                                                            activeSlideIndex === index
                                                                ? "bg-primary text-primary-foreground border-primary scale-110"
                                                                : "bg-background text-muted-foreground border-border hover:border-primary/50"
                                                        )}
                                                    >
                                                        {index + 1}
                                                    </button>
                                                ))}

                                                {/* Next Button */}
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    className="h-8 w-8 p-0"
                                                    onClick={() => setActiveSlideIndex(Math.min(data.slides.length - 1, activeSlideIndex + 1))}
                                                    disabled={activeSlideIndex === data.slides.length - 1}
                                                >
                                                    <ChevronRight className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        )}
                                    </div>
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

                                {/* Carousel View */}
                                {viewMode === 'carousel' && data.slides.length > 0 && (
                                    <div className="space-y-4">
                                        {/* Active Slide Card */}
                                        {data.slides[activeSlideIndex] && (
                                            <Card className="relative overflow-hidden">
                                                <div className="absolute left-0 top-0 bottom-0 w-1 bg-primary" />

                                                {/* Full-Width Image at Top */}
                                                {data.slides[activeSlideIndex].image && (
                                                    <div className="w-full aspect-[21/9] bg-muted relative overflow-hidden">
                                                        <img
                                                            src={data.slides[activeSlideIndex].image}
                                                            alt={`Slide ${activeSlideIndex + 1}`}
                                                            className="w-full h-full object-cover"
                                                        />
                                                    </div>
                                                )}

                                                <CardHeader className="pb-3 bg-muted/40 flex flex-row items-center justify-between space-y-0">
                                                    <div className="flex items-center gap-4">
                                                        <CardTitle className="text-sm font-medium flex items-center gap-2">
                                                            <div className="bg-primary text-primary-foreground border rounded-full w-6 h-6 flex items-center justify-center text-xs">
                                                                {activeSlideIndex + 1}
                                                            </div>
                                                            Slide #{activeSlideIndex + 1}
                                                        </CardTitle>
                                                        <AiAssistButton
                                                            buttonText="Auto-Generate Content"
                                                            size="sm"
                                                            variant="secondary"
                                                            className="h-7 text-xs"
                                                            aiPromptPlaceholder="e.g. A summer clearance sale..."
                                                            systemInstruction='You are an expert copywriter. Generate a banner slide title, subtitle, and short caption. Output MUST be strictly valid JSON only, example: {"title": "...", "subtitle": "...", "caption": "..."}. Do NOT include markdown fences or any other text.'
                                                            onGeneration={(text) => {
                                                                try {
                                                                    const jsonStr = text.replace(/```(?:json)?|```/g, '').trim();
                                                                    const parsed = JSON.parse(jsonStr);
                                                                    if (parsed.title) updateSlide(activeSlideIndex, 'title', parsed.title);
                                                                    if (parsed.subtitle) updateSlide(activeSlideIndex, 'subtitle', parsed.subtitle);
                                                                    if (parsed.caption) updateSlide(activeSlideIndex, 'caption', parsed.caption);
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
                                                        onClick={() => {
                                                            removeSlide(activeSlideIndex);
                                                            if (activeSlideIndex >= data.slides.length - 1) {
                                                                setActiveSlideIndex(Math.max(0, activeSlideIndex - 1));
                                                            }
                                                        }}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </CardHeader>
                                                <CardContent className="p-4 space-y-3">
                                                    {/* Image Picker */}
                                                    <div className="space-y-1.5">
                                                        <Label className="text-xs">Image</Label>
                                                        <ImagePicker
                                                            value={data.slides[activeSlideIndex].image}
                                                            onChange={(url: string | string[]) => updateSlide(activeSlideIndex, 'image', url as string)}
                                                        />
                                                    </div>

                                                    {/* URL (80%) + Sequence (20%) */}
                                                    <div className="grid grid-cols-5 gap-3">
                                                        <div className="space-y-1.5 col-span-4">
                                                            <Label className="text-xs">Target URL</Label>
                                                            <Input
                                                                value={data.slides[activeSlideIndex].url || ''}
                                                                onChange={(e) => updateSlide(activeSlideIndex, 'url', e.target.value)}
                                                                placeholder="https://..."
                                                                className="h-9"
                                                            />
                                                        </div>
                                                        <div className="space-y-1.5 col-span-1">
                                                            <Label className="text-xs">Sequence</Label>
                                                            <Input
                                                                type="number"
                                                                value={data.slides[activeSlideIndex].sequence}
                                                                onChange={(e) => updateSlide(activeSlideIndex, 'sequence', parseInt(e.target.value))}
                                                                className="h-9"
                                                            />
                                                        </div>
                                                    </div>

                                                    {/* Title - Full Width */}
                                                    <div className="space-y-1.5">
                                                        <Label className="text-xs">Title (Overlay)</Label>
                                                        <AiInput
                                                            value={data.slides[activeSlideIndex].title || ''}
                                                            onChange={(e) => updateSlide(activeSlideIndex, 'title', e.target.value)}
                                                            onGeneration={(text) => updateSlide(activeSlideIndex, 'title', text)}
                                                            placeholder="Slide Title (generated via AI)"
                                                            aiPromptLabel="What should this title be about?"
                                                            className="h-9"
                                                        />
                                                    </div>

                                                    {/* Subtitle + Caption (2 columns) */}
                                                    <div className="grid grid-cols-2 gap-3">
                                                        <div className="space-y-1.5">
                                                            <Label className="text-xs">Subtitle</Label>
                                                            <AiInput
                                                                value={data.slides[activeSlideIndex].subtitle || ''}
                                                                onChange={(e) => updateSlide(activeSlideIndex, 'subtitle', e.target.value)}
                                                                onGeneration={(text) => updateSlide(activeSlideIndex, 'subtitle', text)}
                                                                placeholder="Slide Subtitle"
                                                                aiPromptLabel="What should the subtitle emphasize?"
                                                                className="h-9"
                                                            />
                                                        </div>
                                                        <div className="space-y-1.5">
                                                            <Label className="text-xs">Caption</Label>
                                                            <AiInput
                                                                value={data.slides[activeSlideIndex].caption || ''}
                                                                onChange={(e) => updateSlide(activeSlideIndex, 'caption', e.target.value)}
                                                                onGeneration={(text) => updateSlide(activeSlideIndex, 'caption', text)}
                                                                placeholder="Small text"
                                                                aiPromptLabel="Write a short call to action caption."
                                                                className="h-9"
                                                            />
                                                        </div>
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        )}
                                    </div>
                                )}

                                {/* List View */}
                                {viewMode === 'list' && data.slides.map((slide, index) => (
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
                                                    aiPromptPlaceholder="e.g. A summer clearance sale..."
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
                                        <CardContent className="p-4 space-y-3">
                                            {/* Image Thumbnail Preview */}
                                            {slide.image && (
                                                <div className="w-full aspect-[21/9] bg-muted relative overflow-hidden rounded-md">
                                                    <img
                                                        src={slide.image}
                                                        alt={`Slide ${index + 1}`}
                                                        className="w-full h-full object-cover"
                                                    />
                                                </div>
                                            )}

                                            {/* Image Picker */}
                                            <div className="space-y-1.5">
                                                <Label className="text-xs">Image</Label>
                                                <ImagePicker
                                                    value={slide.image}
                                                    onChange={(url: string | string[]) => updateSlide(index, 'image', url as string)}
                                                />
                                            </div>

                                            {/* URL (80%) + Sequence (20%) */}
                                            <div className="grid grid-cols-5 gap-3">
                                                <div className="space-y-1.5 col-span-4">
                                                    <Label className="text-xs">Target URL</Label>
                                                    <Input
                                                        value={slide.url || ''}
                                                        onChange={(e) => updateSlide(index, 'url', e.target.value)}
                                                        placeholder="https://..."
                                                        className="h-9"
                                                    />
                                                </div>
                                                <div className="space-y-1.5 col-span-1">
                                                    <Label className="text-xs">Sequence</Label>
                                                    <Input
                                                        type="number"
                                                        value={slide.sequence}
                                                        onChange={(e) => updateSlide(index, 'sequence', parseInt(e.target.value))}
                                                        className="h-9"
                                                    />
                                                </div>
                                            </div>

                                            {/* Title - Full Width */}
                                            <div className="space-y-1.5">
                                                <Label className="text-xs">Title (Overlay)</Label>
                                                <AiInput
                                                    value={slide.title || ''}
                                                    onChange={(e) => updateSlide(index, 'title', e.target.value)}
                                                    onGeneration={(text) => updateSlide(index, 'title', text)}
                                                    placeholder="Slide Title (generated via AI)"
                                                    aiPromptLabel="What should this title be about?"
                                                    className="h-9"
                                                />
                                            </div>

                                            {/* Subtitle + Caption (2 columns) */}
                                            <div className="grid grid-cols-2 gap-3">
                                                <div className="space-y-1.5">
                                                    <Label className="text-xs">Subtitle</Label>
                                                    <AiInput
                                                        value={slide.subtitle || ''}
                                                        onChange={(e) => updateSlide(index, 'subtitle', e.target.value)}
                                                        onGeneration={(text) => updateSlide(index, 'subtitle', text)}
                                                        placeholder="Slide Subtitle"
                                                        aiPromptLabel="What should the subtitle emphasize?"
                                                        className="h-9"
                                                    />
                                                </div>
                                                <div className="space-y-1.5">
                                                    <Label className="text-xs">Caption</Label>
                                                    <AiInput
                                                        value={slide.caption || ''}
                                                        onChange={(e) => updateSlide(index, 'caption', e.target.value)}
                                                        onGeneration={(text) => updateSlide(index, 'caption', text)}
                                                        placeholder="Small text"
                                                        aiPromptLabel="Write a short call to action caption."
                                                        className="h-9"
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
                    canDelete={true}
                    onDelete={handleDelete}
                />
            </form>
        </AuthenticatedLayout>
    );
}

import React, { useState } from 'react';
import { Textarea } from '@/Components/ui/textarea';
import { Button } from '@/Components/ui/button';
import { Wand2, Loader2 } from 'lucide-react';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/Components/ui/popover';
import { Label } from '@/Components/ui/label';
import axios from 'axios';
import { toast } from 'sonner';

interface AiTextareaProps extends React.ComponentProps<'textarea'> {
    onGeneration: (generatedText: string) => void;
    aiPromptLabel?: string;
    aiPromptPlaceholder?: string;
}

export function AiTextarea({
    className,
    onGeneration,
    aiPromptLabel = "What should the AI write?",
    aiPromptPlaceholder = "e.g. A detailed description of our new summer collection...",
    ...props
}: AiTextareaProps) {
    const [prompt, setPrompt] = useState("");
    const [isGenerating, setIsGenerating] = useState(false);
    const [isOpen, setIsOpen] = useState(false);

    const handleGenerate = async () => {
        if (!prompt.trim()) return;

        setIsGenerating(true);
        try {
            const response = await axios.post(route('admin.ai.generate'), {
                prompt: prompt
            });

            if (response.data && response.data.generated_text) {
                onGeneration(response.data.generated_text);
                setIsOpen(false);
                setPrompt("");
                toast.success('Text generated successfully');
            }
        } catch (error: any) {
            const message = error.response?.data?.error || 'Failed to generate text. Ensure an AI Engine is active.';
            toast.error(message);
        } finally {
            setIsGenerating(false);
        }
    };

    return (
        <div className="relative flex w-full">
            <Textarea className={`pr-10 min-h-[100px] ${className || ''}`} {...props} />

            <Popover open={isOpen} onOpenChange={setIsOpen}>
                <PopoverTrigger asChild>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="absolute right-2 top-2 h-7 w-7 text-muted-foreground hover:text-primary"
                        title="Generate with AI"
                    >
                        <Wand2 className="h-4 w-4" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-80" align="end">
                    <div className="grid gap-4">
                        <div className="space-y-2">
                            <h4 className="font-medium leading-none flex items-center gap-2">
                                <Wand2 className="h-4 w-4 text-primary" />
                                AI Assistant
                            </h4>
                            <p className="text-sm text-muted-foreground">
                                Describe what you want the AI to write for this field.
                            </p>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="ai-prompt-textarea" className="sr-only">{aiPromptLabel}</Label>
                            <Textarea
                                id="ai-prompt-textarea"
                                placeholder={aiPromptPlaceholder}
                                value={prompt}
                                onChange={(e) => setPrompt(e.target.value)}
                                rows={4}
                                className="resize-none"
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter' && !e.shiftKey) {
                                        e.preventDefault();
                                        handleGenerate();
                                    }
                                }}
                            />
                        </div>
                        <Button
                            onClick={handleGenerate}
                            disabled={isGenerating || !prompt.trim()}
                            className="w-full"
                        >
                            {isGenerating ? (
                                <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Generating...
                                </>
                            ) : (
                                'Generate'
                            )}
                        </Button>
                    </div>
                </PopoverContent>
            </Popover>
        </div>
    );
}

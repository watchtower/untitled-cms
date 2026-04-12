import React, { useState } from 'react';
import { Button } from '@/Components/ui/button';
import { Loader2, Sparkles } from 'lucide-react';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/Components/ui/popover';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import axios from 'axios';
import { toast } from 'sonner';

interface AiAssistButtonProps {
    onGeneration: (generatedText: string) => void;
    aiPromptLabel?: string;
    aiPromptPlaceholder?: string;
    buttonText?: string;
    variant?: "default" | "destructive" | "outline" | "secondary" | "ghost" | "link";
    size?: "default" | "sm" | "lg" | "icon";
    className?: string;
    systemInstruction?: string;
}

export function AiAssistButton({
    onGeneration,
    aiPromptLabel = "What should the AI write?",
    aiPromptPlaceholder = "e.g. Write a comprehensive guide on...",
    buttonText = "AI Assist",
    variant = "outline",
    size = "sm",
    className = "",
    systemInstruction
}: AiAssistButtonProps) {
    const [prompt, setPrompt] = useState("");
    const [isGenerating, setIsGenerating] = useState(false);
    const [isOpen, setIsOpen] = useState(false);

    const handleGenerate = async () => {
        if (!prompt.trim()) return;

        setIsGenerating(true);
        try {
            const finalPrompt = systemInstruction ? `${systemInstruction}\n\nUser Request: ${prompt}` : prompt;

            const response = await axios.post(route('admin.ai.generate'), {
                prompt: finalPrompt
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
        <Popover open={isOpen} onOpenChange={setIsOpen}>
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant={variant}
                    size={size}
                    className={className}
                    title="Generate with AI"
                >
                    <Sparkles className="h-4 w-4 mr-2" />
                    {buttonText}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-96" align="start" sideOffset={8}>
                <div className="grid gap-4">
                    <div className="space-y-2">
                        <h4 className="font-medium leading-none flex items-center gap-2">
                            <Sparkles className="h-4 w-4 text-primary" />
                            AI Content Assistant
                        </h4>
                        <p className="text-sm text-muted-foreground">
                            Describe what you want the AI to write. The generated text will be appended to your editor.
                        </p>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="ai-prompt" className="sr-only">{aiPromptLabel}</Label>
                        <Textarea
                            id="ai-prompt"
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
                            'Generate Content'
                        )}
                    </Button>
                </div>
            </PopoverContent>
        </Popover>
    );
}

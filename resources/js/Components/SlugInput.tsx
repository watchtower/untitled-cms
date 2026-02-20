
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";
import { Lock, Unlock, Link as LinkIcon } from "lucide-react";
import { useState, useEffect } from "react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/Components/ui/tooltip";
import { cn } from "@/lib/utils";

interface SlugInputProps {
    value: string;
    onChange: (value: string) => void;
    sourceValue?: string; // The title/name to generate from
    isEditing: boolean; // Are we in Edit mode? (vs Create)
    className?: string;
    baseUrl?: string;
}

export function SlugInput({
    value,
    onChange,
    sourceValue,
    isEditing,
    className,
    baseUrl = typeof window !== 'undefined' ? window.location.origin + '/' : '/'
}: SlugInputProps) {
    const [isLocked, setIsLocked] = useState(isEditing);

    // Auto-generate slug from source (Name) only if NOT locked AND not in edit mode (to prevent accidental overrides)
    useEffect(() => {
        // In creation mode: Sync if unlocked.
        // In edit mode: Never sync automatically even if unlocked, to preserve SEO. User must edit manually.
        if (!isLocked && sourceValue && !isEditing) {
            const slug = sourceValue
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)+/g, '');
            onChange(slug);
        }
    }, [sourceValue, isLocked, onChange, isEditing]);

    return (
        <div className={cn("relative flex items-center", className)}>
            <div className="flex h-10 items-center rounded-l-md border border-r-0 bg-muted px-3 text-sm text-muted-foreground whitespace-nowrap">
                {baseUrl.replace(/^https?:\/\//, '')}
            </div>
            <Input
                value={value}
                onChange={(e) => onChange(e.target.value)}
                className="flex-1 min-w-0 rounded-l-none rounded-r-none border-x-0 focus-visible:ring-0 focus-visible:ring-offset-0"
                readOnly={isLocked}
                placeholder="url-slug"
            />
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            className="rounded-l-none border-l-0"
                            onClick={() => setIsLocked(!isLocked)}
                        >
                            {isLocked ? (
                                <Lock className="h-4 w-4 text-muted-foreground" />
                            ) : (
                                <Unlock className="h-4 w-4 text-primary" />
                            )}
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                        {isLocked
                            ? "Unlock to edit URL (Caution: Breaks links)"
                            : "Lock to prevent auto-updates"}
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        </div>
    );
}

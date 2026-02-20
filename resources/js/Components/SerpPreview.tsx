
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Globe } from "lucide-react";

interface SerpPreviewProps {
    title: string;
    description: string;
    slug: string;
    siteName?: string;
}

export function SerpPreview({
    title,
    description,
    slug,
    siteName = 'MySite'
}: SerpPreviewProps) {
    const displayTitle = title || "Page Title";
    const displayDesc = description || "Meta description will appear here...";
    const url = `${typeof window !== 'undefined' ? window.location.origin : 'https://example.com'}/${slug || 'slug'}`;

    return (
        <Card className="overflow-hidden bg-white dark:bg-zinc-900 border-dashed">
            <CardHeader className="pb-3 bg-muted/20 border-b border-dashed">
                <CardTitle className="text-sm font-medium flex items-center gap-2 text-muted-foreground">
                    <Globe className="h-4 w-4" />
                    Search Engine Preview
                </CardTitle>
            </CardHeader>
            <CardContent className="p-4 space-y-1">
                {/* Google-like Result */}
                <div className="flex items-center gap-2 text-xs text-[#202124] dark:text-[#bdc1c6] mb-1">
                    <div className="h-6 w-6 rounded-full bg-gray-100 flex items-center justify-center text-[10px] overflow-hidden">
                        logo
                    </div>
                    <div className="flex flex-col">
                        <span className="font-medium">{siteName}</span>
                        <span className="text-muted-foreground truncate max-w-[300px]">{url}</span>
                    </div>
                </div>

                <h3 className="text-xl text-[#1a0dab] dark:text-[#8ab4f8] font-medium hover:underline cursor-pointer truncate">
                    {displayTitle.length > 60 ? displayTitle.substring(0, 60) + '...' : displayTitle}
                </h3>

                <p className="text-sm text-[#4d5156] dark:text-[#bdc1c6] leading-relaxed max-w-[600px]">
                    {/* Date if applicable could go here */}
                    {displayDesc.length > 160 ? displayDesc.substring(0, 160) + '...' : displayDesc}
                </p>

                {/* Counter Validation */}
                <div className="flex gap-4 mt-4 text-[10px] uppercase tracking-wider font-semibold text-muted-foreground">
                    <span className={title.length > 60 ? "text-red-500" : "text-green-600"}>
                        Title: {title.length}/60
                    </span>
                    <span className={description.length > 160 ? "text-red-500" : "text-green-600"}>
                        Desc: {description.length}/160
                    </span>
                </div>
            </CardContent>
        </Card>
    );
}

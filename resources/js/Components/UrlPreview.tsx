import { ExternalLink } from 'lucide-react';
import { cn } from '@/lib/utils';

interface UrlPreviewProps {
    slug: string;
    baseUrl?: string;
    className?: string;
}

export function UrlPreview({ slug, baseUrl, className }: UrlPreviewProps) {
    const fullUrl = `${baseUrl || window.location.origin}/${slug || 'your-slug'}`;

    return (
        <div className={cn('flex items-center gap-2 text-sm text-muted-foreground', className)}>
            <ExternalLink className="h-3 w-3" />
            <a
                href={fullUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="hover:text-primary hover:underline truncate"
            >
                {fullUrl}
            </a>
        </div>
    );
}

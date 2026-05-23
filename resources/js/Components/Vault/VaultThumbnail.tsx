import { VaultFile } from '@/types/vault';
import VaultFileIcon from './VaultFileIcon';
import { cn } from '@/lib/utils';
import { Check, Zap } from 'lucide-react';

interface VaultThumbnailProps {
    file: VaultFile;
    selected?: boolean;
    onSelect?: (e: React.MouseEvent<HTMLDivElement>) => void;
    onDoubleClick?: () => void;
    className?: string;
}

export default function VaultThumbnail({
    file,
    selected,
    onSelect,
    onDoubleClick,
    className
}: VaultThumbnailProps) {
    const isImage = file.mime_type.startsWith('image/');

    return (
        <div
            className={cn(
                "group relative flex flex-col items-center gap-2 rounded-lg border p-4 hover:bg-accent cursor-pointer transition-colors",
                selected && "border-primary ring-1 ring-primary bg-accent/50",
                className
            )}
            onClick={onSelect}
            onDoubleClick={onDoubleClick}
        >
            {/* Selection Checkmark */}
            {selected && (
                <div className="absolute top-2 right-2 bg-primary text-primary-foreground rounded-full p-0.5 shadow-xs z-10">
                    <Check className="h-3 w-3" />
                </div>
            )}

            {/* Thumbnail / Icon */}
            <div className="aspect-square w-full relative overflow-hidden rounded-md bg-muted/20 flex items-center justify-center">
                {isImage && file.url ? (
                    <>
                        <img
                            src={file.url}
                            alt={file.alt_text || file.original_name}
                            className="object-cover w-full h-full"
                            loading="lazy"
                        />
                        {/* WebP Indicator */}
                        {file.is_optimized && !file.use_original && (
                            <div className="absolute top-1 left-1 bg-black/60 backdrop-blur-xs rounded p-0.5 shadow-xs">
                                <Zap className="h-3 w-3 text-amber-400 fill-amber-400" />
                            </div>
                        )}
                    </>
                ) : (
                    <VaultFileIcon mimeType={file.mime_type} className="h-12 w-12 text-muted-foreground" />
                )}
            </div>

            {/* Metadata */}
            <div className="w-full text-center">
                <p className="text-sm font-medium truncate w-full" title={file.original_name}>
                    {file.original_name}
                </p>
                <p className="text-xs text-muted-foreground">
                    {(file.size_bytes / 1024).toFixed(1)} KB
                </p>
            </div>
        </div>
    );
}

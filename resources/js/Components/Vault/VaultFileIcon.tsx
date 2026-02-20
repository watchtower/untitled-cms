import { File, FileImage, FileText, FileVideo, Music, Archive, Code } from 'lucide-react';

interface VaultFileIconProps {
    mimeType: string;
    className?: string;
}

export default function VaultFileIcon({ mimeType, className }: VaultFileIconProps) {
    if (mimeType.startsWith('image/')) return <FileImage className={className} />;
    if (mimeType.startsWith('video/')) return <FileVideo className={className} />;
    if (mimeType.startsWith('audio/')) return <Music className={className} />;
    if (mimeType === 'application/pdf') return <FileText className={className} />;
    if (
        mimeType.includes('word') ||
        mimeType.includes('document') ||
        mimeType.includes('text')
    ) return <FileText className={className} />;

    if (
        mimeType.includes('zip') ||
        mimeType.includes('compressed') ||
        mimeType.includes('tar')
    ) return <Archive className={className} />;

    if (
        mimeType.includes('json') ||
        mimeType.includes('xml') ||
        mimeType.includes('javascript') ||
        mimeType.includes('html')
    ) return <Code className={className} />;

    return <File className={className} />;
}

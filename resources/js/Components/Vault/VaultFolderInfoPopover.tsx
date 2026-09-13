import { Button } from '@/Components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/Components/ui/popover';
import { VaultFolder } from '@/types/vault';
import { Info } from 'lucide-react';

export default function VaultFolderInfoPopover({
    folder,
    side = 'right',
}: {
    folder: VaultFolder;
    side?: 'left' | 'right' | 'top' | 'bottom';
}) {
    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className={
                        side === 'right'
                            ? 'h-6 w-6 rounded-full hover:bg-background/80'
                            : 'h-8 w-8 rounded-full'
                    }
                >
                    <Info className="h-4 w-4 text-muted-foreground" />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                className="w-56 z-100"
                side={side}
                align={side === 'right' ? 'start' : 'center'}
                onClick={(e: React.MouseEvent) => e.stopPropagation()}
            >
                <div className="space-y-2">
                    <h4 className="font-semibold text-sm break-all" title={folder.name}>
                        {folder.name}
                    </h4>
                    <div className="grid grid-cols-2 gap-1 text-sm text-muted-foreground">
                        <span>Total Items:</span>
                        <span className="text-foreground text-right">
                            {folder.files_count || 0}
                        </span>
                        <span>Total Size:</span>
                        <span className="text-foreground text-right">
                            {((folder.files_size || 0) / 1024).toFixed(1)} KB
                        </span>
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    );
}

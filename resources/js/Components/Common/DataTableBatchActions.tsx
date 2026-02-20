import { Trash2, CheckCircle, XCircle, X } from "lucide-react";
import { Button } from "@/Components/ui/button";
import { Separator } from "@/Components/ui/separator";

interface DataTableBatchActionsProps {
    selectedCount: number;
    onActivate: () => void;
    onDeactivate: () => void;
    onDelete: () => void;
    onClear: () => void;
}

export function DataTableBatchActions({
    selectedCount,
    onActivate,
    onDeactivate,
    onDelete,
    onClear,
}: DataTableBatchActionsProps) {
    if (selectedCount === 0) return null;

    return (
        <div className="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 animate-in fade-in slide-in-from-bottom-4 duration-300">
            <div className="flex items-center gap-2 rounded-lg border bg-background p-2 shadow-2xl">
                <div className="flex items-center gap-3 px-3">
                    <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-primary text-xs font-bold text-primary-foreground">
                        {selectedCount}
                    </span>
                    <span className="text-sm font-medium">selected</span>
                </div>

                <Separator orientation="vertical" className="h-8" />

                <div className="flex items-center">
                    <Button
                        variant="ghost"
                        size="sm"
                        className="h-9 rounded-r-none border-r"
                        onClick={onActivate}
                    >
                        <CheckCircle className="mr-2 h-4 w-4" />
                        Activate
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        className="h-9 rounded-none border-r"
                        onClick={onDeactivate}
                    >
                        <XCircle className="mr-2 h-4 w-4" />
                        Deactivate
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        className="h-9 rounded-l-none text-destructive hover:text-destructive"
                        onClick={onDelete}
                    >
                        <Trash2 className="mr-2 h-4 w-4" />
                        Delete
                    </Button>
                </div>

                <Separator orientation="vertical" className="h-8" />

                <Button
                    variant="ghost"
                    size="icon"
                    className="h-9 w-9"
                    onClick={onClear}
                >
                    <X className="h-4 w-4" />
                </Button>
            </div>
        </div>
    );
}


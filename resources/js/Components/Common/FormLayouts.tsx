
import { Button } from "@/Components/ui/button";
import { Separator } from "@/Components/ui/separator";
import { cn } from "@/lib/utils";
import { ReactNode } from "react";

interface StickyFormFooterProps {
    onSave?: () => void;
    onDelete?: () => void;
    isSaving?: boolean;
    isDirty?: boolean;
    canDelete?: boolean;
    lastSaved?: string | null;
    className?: string;
}

export function StickyFormFooter({
    onSave,
    onDelete,
    isSaving = false,
    isDirty = false,
    canDelete = false,
    lastSaved,
    className
}: StickyFormFooterProps) {
    return (
        <div className={cn(
            "fixed bottom-0 left-0 right-0 z-40 flex items-center justify-between border-t bg-background p-4 shadow-lg md:left-(--sidebar-width) transition-[left] duration-200",
            className
        )}>
            <div className="flex items-center gap-2">
                {canDelete && onDelete && (
                    <Button
                        type="button"
                        variant="destructive"
                        onClick={onDelete}
                        disabled={isSaving}
                    >
                        Delete
                    </Button>
                )}
                {lastSaved && (
                    <span className="text-xs text-muted-foreground hidden sm:inline-block">
                        Last saved: {lastSaved}
                    </span>
                )}
            </div>
            <div className="flex items-center gap-4">
                {isDirty && <span className="text-sm text-yellow-600 hidden sm:inline-block">Unsaved Changes</span>}
                <Button
                    type="submit"
                    onClick={onSave}
                    disabled={isSaving || !isDirty}
                >
                    {isSaving ? "Saving..." : "Save Changes"}
                </Button>
            </div>
        </div>
    );
}

export function FormSplitLayout({
    children,
    sidebar,
    className,
    isExpanded = false
}: {
    children: ReactNode;
    sidebar: ReactNode;
    className?: string;
    isExpanded?: boolean;
}) {
    return (
        <div className={cn(
            "grid grid-cols-1 gap-6 lg:gap-8 pb-20",
            !isExpanded && "md:grid-cols-3",
            className
        )}>
            <div className={cn(
                "space-y-6",
                !isExpanded && "md:col-span-2"
            )}>
                {children}
            </div>
            <div className={cn(
                !isExpanded ? "space-y-6 md:col-span-1" : "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6 items-start"
            )}>
                {sidebar}
            </div>
        </div>
    );
}

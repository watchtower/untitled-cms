import { Badge } from "@/Components/ui/badge";
import { cn } from "@/lib/utils";

interface UserStatusBadgeProps {
    isActive: boolean;
    isDeleted?: boolean;
    className?: string;
}

export function UserStatusBadge({ isActive, isDeleted, className }: UserStatusBadgeProps) {
    if (isDeleted) {
        return (
            <Badge variant="destructive" className={cn("capitalize", className)}>
                Deleted
            </Badge>
        );
    }

    if (isActive) {
        return (
            <Badge variant="default" className={cn("bg-emerald-500 hover:bg-emerald-600 capitalize", className)}>
                Active
            </Badge>
        );
    }

    return (
        <Badge variant="secondary" className={cn("capitalize", className)}>
            Inactive
        </Badge>
    );
}

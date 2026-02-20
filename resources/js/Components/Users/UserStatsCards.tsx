import { Users, UserCheck, UserMinus, UserX } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { cn } from "@/lib/utils";

interface UserStats {
    total: number;
    active: number;
    inactive: number;
    deleted: number;
}

interface UserStatsCardsProps {
    stats: UserStats;
    onCardClick?: (type: 'total' | 'active' | 'inactive' | 'deleted') => void;
}

export function UserStatsCards({ stats, onCardClick }: UserStatsCardsProps) {
    const items = [
        {
            type: 'total' as const,
            title: "Total Users",
            value: stats.total,
            icon: Users,
            description: "All registered users",
            className: "text-blue-500",
        },
        {
            type: 'active' as const,
            title: "Active Users",
            value: stats.active,
            icon: UserCheck,
            description: "Users with active status",
            className: "text-emerald-500",
        },
        {
            type: 'inactive' as const,
            title: "Inactive Users",
            value: stats.inactive,
            icon: UserMinus,
            description: "Users currently deactivated",
            className: "text-amber-500",
        },
        {
            type: 'deleted' as const,
            title: "Deleted Users",
            value: stats.deleted,
            icon: UserX,
            description: "Click to view and restore",
            className: "text-rose-500",
        },
    ];

    return (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            {items.map((item) => (
                <Card
                    key={item.title}
                    className={cn(
                        onCardClick && "cursor-pointer transition-all hover:shadow-md hover:scale-[1.02]"
                    )}
                    onClick={() => onCardClick?.(item.type)}
                >
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">
                            {item.title}
                        </CardTitle>
                        <item.icon className={`h-4 w-4 ${item.className}`} />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{item.value}</div>
                        <p className="text-xs text-muted-foreground">
                            {item.description}
                        </p>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}


import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';

interface ActivityLog {
    id: string;
    description: string;
    action: string;
    ip_address: string;
    created_at: string;
    user: {
        name: string;
    } | null;
}

interface Props {
    logs: {
        data: ActivityLog[];
        links: any[];
    };
}

export default function Index({ logs }: Props) {
    const getActionVariant = (action: string): "default" | "secondary" | "destructive" => {
        if (action === 'create') return 'default';
        if (action === 'delete') return 'destructive';
        return 'secondary';
    };

    return (
        <AuthenticatedLayout header="Activity Log">
            <Head title="Activity Log" />

            <div className="flex flex-col gap-4">
                <h1 className="text-2xl font-bold">Activity Log</h1>

                <div className="border rounded-lg">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Time</TableHead>
                                <TableHead>User</TableHead>
                                <TableHead>Action</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead>IP Address</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {logs.data.map((log) => (
                                <TableRow key={log.id}>
                                    <TableCell className="text-muted-foreground text-sm">
                                        {new Date(log.created_at).toLocaleString()}
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        {log.user?.name || 'System/Guest'}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant={getActionVariant(log.action)}>
                                            {log.action}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {log.description}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground text-sm">
                                        {log.ip_address}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

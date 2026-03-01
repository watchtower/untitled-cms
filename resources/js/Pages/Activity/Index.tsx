import { useState } from 'react';
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
import { Button } from '@/Components/ui/button';
import { RotateCcw, Loader2, Sparkles } from 'lucide-react';
import axios from 'axios';
import { toast } from 'sonner';

interface ActivityLog {
    id: string;
    description: string;
    action: string;
    ip_address: string;
    created_at: string;
    is_ai_action?: boolean;
    before_state?: Record<string, any> | null;
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
    const [reverting, setReverting] = useState<string | null>(null);

    const getActionVariant = (action: string): "default" | "secondary" | "destructive" => {
        if (action === 'create' || action === 'ai_created') return 'default';
        if (action === 'delete') return 'destructive';
        return 'secondary';
    };

    const handleRevert = async (logId: string, description: string, hasBeforeState: boolean) => {
        const actionLabel = hasBeforeState ? 'Restore previous values for' : 'Undo (soft-delete) the AI-created record for';
        if (!confirm(`${actionLabel}:\n\n"${description}"\n\nAre you sure?`)) return;

        setReverting(logId);
        try {
            const { data } = await axios.post(`/ai/actions/revert/${logId}`);
            const actionType = data.result?.action_type;
            if (actionType === 'deleted') {
                toast.success('AI-created record removed (soft-deleted).');
            } else {
                toast.success('Record restored to its previous state.');
            }
        } catch (error: any) {
            toast.error(error.response?.data?.error || 'Revert failed.');
        } finally {
            setReverting(null);
        }
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
                                <TableHead></TableHead>
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
                                        <div className="flex items-center gap-1.5">
                                            <Badge variant={getActionVariant(log.action)}>
                                                {log.action}
                                            </Badge>
                                            {log.is_ai_action && (
                                                <span title="AI action">
                                                    <Sparkles className="h-3 w-3 text-primary" />
                                                </span>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {log.description}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground text-sm">
                                        {log.ip_address}
                                    </TableCell>
                                    <TableCell>
                                        {log.is_ai_action && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="h-7 text-xs gap-1 text-muted-foreground hover:text-foreground"
                                                onClick={() => handleRevert(log.id, log.description, !!log.before_state)}
                                                disabled={reverting === log.id}
                                            >
                                                {reverting === log.id
                                                    ? <Loader2 className="h-3 w-3 animate-spin" />
                                                    : <RotateCcw className="h-3 w-3" />
                                                }
                                                {log.before_state ? 'Restore' : 'Undo'}
                                            </Button>
                                        )}
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

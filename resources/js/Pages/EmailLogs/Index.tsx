import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    Table, 
    TableBody, 
    TableCell, 
    TableHead, 
    TableHeader, 
    TableRow 
} from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Button } from '@/Components/ui/button';
import { 
    Mail, 
    CheckCircle2, 
    Eye, 
    XCircle, 
    Search,
    Filter,
    ChevronLeft,
    ChevronRight
} from 'lucide-react';

interface Log {
    id: string;
    provider_message_id: string;
    recipient: string;
    subject: string;
    status: string;
    mailable: string;
    created_at: string;
    delivered_at?: string;
    opened_at?: string;
}

interface Props {
    logs: {
        data: Log[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    stats: {
        total: number;
        delivery_rate: number;
        open_rate: number;
        bounce_rate: number;
    };
    filters: {
        status?: string;
        search?: string;
    };
}

export default function EmailLogIndex({ logs, stats, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');

    const handleFilter = () => {
        router.get(route('admin.email-logs.index'), {
            search: search,
            status: status === 'all' ? null : status,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'sent': return <Badge variant="secondary">Sent</Badge>;
            case 'delivered': return <Badge variant="default" className="bg-green-500">Delivered</Badge>;
            case 'opened': return <Badge variant="default" className="bg-blue-500">Opened</Badge>;
            case 'clicked': return <Badge variant="default" className="bg-indigo-500">Clicked</Badge>;
            case 'bounced': return <Badge variant="destructive">Bounced</Badge>;
            case 'complained': return <Badge variant="destructive">Spam</Badge>;
            default: return <Badge variant="outline">{status}</Badge>;
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="Email Logs" />

            <div className="p-6 space-y-6">
                <div className="flex justify-between items-center">
                    <h1 className="text-3xl font-bold tracking-tight">Email Logs</h1>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Sent</CardTitle>
                            <Mail className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Delivery Rate</CardTitle>
                            <CheckCircle2 className="h-4 w-4 text-green-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.delivery_rate}%</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Open Rate</CardTitle>
                            <Eye className="h-4 w-4 text-blue-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.open_rate}%</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Bounce Rate</CardTitle>
                            <XCircle className="h-4 w-4 text-destructive" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.bounce_rate}%</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <div className="flex flex-col md:flex-row gap-4 items-end bg-white p-4 rounded-lg shadow-xs border">
                    <div className="flex-1 space-y-2">
                        <label className="text-sm font-medium">Search Recipient or Subject</label>
                        <div className="relative">
                            <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input 
                                placeholder="Search..." 
                                className="pl-8" 
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </div>
                    <div className="w-full md:w-48 space-y-2">
                        <label className="text-sm font-medium">Status</label>
                        <Select value={status} onValueChange={setStatus}>
                            <SelectTrigger>
                                <SelectValue placeholder="All Statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Statuses</SelectItem>
                                <SelectItem value="sent">Sent</SelectItem>
                                <SelectItem value="delivered">Delivered</SelectItem>
                                <SelectItem value="opened">Opened</SelectItem>
                                <SelectItem value="clicked">Clicked</SelectItem>
                                <SelectItem value="bounced">Bounced</SelectItem>
                                <SelectItem value="complained">Spam</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <Button onClick={handleFilter}>
                        <Filter className="h-4 w-4 mr-2" />
                        Filter
                    </Button>
                </div>

                {/* Table */}
                <div className="bg-white rounded-lg shadow-xs border overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Status</TableHead>
                                <TableHead>Recipient</TableHead>
                                <TableHead>Subject</TableHead>
                                <TableHead>Mailable</TableHead>
                                <TableHead>Sent At</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {logs.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-center py-12 text-muted-foreground">
                                        No email logs found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                logs.data.map((log) => (
                                    <TableRow key={log.id}>
                                        <TableCell>{getStatusBadge(log.status)}</TableCell>
                                        <TableCell className="font-medium">{log.recipient}</TableCell>
                                        <TableCell className="max-w-xs truncate">{log.subject}</TableCell>
                                        <TableCell className="text-xs text-muted-foreground font-mono">
                                            {log.mailable?.split('\\').pop()}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {new Date(log.created_at).toLocaleString()}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>

                    {/* Pagination */}
                    <div className="flex items-center justify-between px-4 py-4 border-t">
                        <div className="text-sm text-muted-foreground">
                            Page {logs.current_page} of {logs.last_page}
                        </div>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={logs.current_page === 1}
                                onClick={() => {
                                    const link = logs.links.find(l => l.label === '&laquo; Previous');
                                    if (link?.url) router.get(link.url);
                                }}
                            >
                                <ChevronLeft className="h-4 w-4" />
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={logs.current_page === logs.last_page}
                                onClick={() => {
                                    const link = logs.links.find(l => l.label === 'Next &raquo;');
                                    if (link?.url) router.get(link.url);
                                }}
                            >
                                <ChevronRight className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

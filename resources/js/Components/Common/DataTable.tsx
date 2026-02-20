import {
    ColumnDef,
    flexRender,
    getCoreRowModel,
    SortingState,
    getSortedRowModel,
    useReactTable,
    getPaginationRowModel,
    VisibilityState,
    ColumnFiltersState,
    getFilteredRowModel,
    getFacetedRowModel,
    getFacetedUniqueValues,
    Table as TanstackTable,
} from "@tanstack/react-table";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import { Card, CardContent } from "@/Components/ui/card";
import { cn } from "@/lib/utils";
import { useState, useEffect } from "react";
import { DataTablePagination } from "./DataTablePagination";

interface DataTableProps<TData, TValue> {
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
    mobileCardRenderer?: (row: TData) => React.ReactNode;
    onRowSelectionChange?: (selectedRows: TData[]) => void;
    children?: (props: { table: TanstackTable<TData> }) => React.ReactNode;
    initialColumnFilters?: ColumnFiltersState;
}

export function DataTable<TData, TValue>({
    columns,
    data,
    mobileCardRenderer,
    onRowSelectionChange,
    children,
    initialColumnFilters,
}: DataTableProps<TData, TValue>) {
    const [sorting, setSorting] = useState<SortingState>([]);
    const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>(initialColumnFilters || []);
    const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({});
    const [rowSelection, setRowSelection] = useState({});

    // Sync external filters
    useEffect(() => {
        if (initialColumnFilters && JSON.stringify(initialColumnFilters) !== JSON.stringify(columnFilters)) {
            setColumnFilters(initialColumnFilters);
        }
    }, [initialColumnFilters]);

    const table = useReactTable({
        data,
        columns,
        state: {
            sorting,
            columnFilters,
            columnVisibility,
            rowSelection,
        },
        enableRowSelection: true,
        onRowSelectionChange: setRowSelection,
        onSortingChange: setSorting,
        onColumnFiltersChange: setColumnFilters,
        onColumnVisibilityChange: setColumnVisibility,
        getCoreRowModel: getCoreRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getFacetedRowModel: getFacetedRowModel(),
        getFacetedUniqueValues: getFacetedUniqueValues(),
    });

    return (
        <div className="space-y-4">
            {/* Render toolbar and other controls first */}
            {children && children({ table })}

            {/* Desktop View (Table) - Hidden on Mobile */}
            <div className="hidden rounded-md border md:block bg-background">
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => {
                                    return (
                                        <TableHead key={header.id}>
                                            {header.isPlaceholder
                                                ? null
                                                : flexRender(
                                                    header.column.columnDef.header,
                                                    header.getContext()
                                                )}
                                        </TableHead>
                                    );
                                })}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {table.getRowModel().rows?.length ? (
                            table.getRowModel().rows.map((row) => (
                                <TableRow
                                    key={row.id}
                                    data-state={row.getIsSelected() && "selected"}
                                >
                                    {row.getVisibleCells().map((cell) => (
                                        <TableCell key={cell.id}>
                                            {flexRender(
                                                cell.column.columnDef.cell,
                                                cell.getContext()
                                            )}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    className="h-24 text-center"
                                >
                                    No results.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            {/* Mobile View (Cards) - Hidden on Desktop */}
            <div className="space-y-4 md:hidden">
                {table.getRowModel().rows?.length ? (
                    table.getRowModel().rows.map((row) => (
                        <Card key={row.id} className="overflow-hidden">
                            {mobileCardRenderer ? (
                                mobileCardRenderer(row.original)
                            ) : (
                                // Fallback: Render first 2 columns as Title/Desc
                                <CardContent className="p-4 space-y-2">
                                    {row.getVisibleCells().slice(0, 2).map(cell => (
                                        <div key={cell.id} className="flex flex-col">
                                            <span className="text-xs font-semibold uppercase text-muted-foreground mb-1">
                                                {cell.column.id}
                                            </span>
                                            <span>
                                                {flexRender(
                                                    cell.column.columnDef.cell,
                                                    cell.getContext()
                                                )}
                                            </span>
                                        </div>
                                    ))}
                                </CardContent>
                            )}
                        </Card>
                    ))
                ) : (
                    <div className="text-center p-8 text-muted-foreground border rounded-lg border-dashed">
                        No results found.
                    </div>
                )}
            </div>

            {/* Pagination Controls */}
            <DataTablePagination table={table} />
        </div>
    );
}

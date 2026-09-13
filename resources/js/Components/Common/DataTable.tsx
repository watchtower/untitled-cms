import {
    columnFacetingFeature,
    columnFilteringFeature,
    columnVisibilityFeature,
    createFacetedRowModel,
    createFacetedUniqueValues,
    createFilteredRowModel,
    createPaginatedRowModel,
    createSortedRowModel,
    filterFns,
    flexRender,
    rowPaginationFeature,
    rowSelectionFeature,
    rowSortingFeature,
    sortFns,
    tableFeatures,
    useTable,
    type CellData,
    type ColumnDef,
    type ColumnFiltersState,
    type ColumnVisibilityState,
    type ReactTable,
    type RowData,
    type RowSelectionState,
    type SortingState,
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

// Features shared by every admin data table. The full filter/sort registries are
// registered so "auto" filter and sort resolution matches TanStack Table v8.
export const dataTableFeatures = tableFeatures({
    columnFilteringFeature,
    columnFacetingFeature,
    columnVisibilityFeature,
    rowPaginationFeature,
    rowSelectionFeature,
    rowSortingFeature,
    filteredRowModel: createFilteredRowModel(),
    facetedRowModel: createFacetedRowModel(),
    facetedUniqueValues: createFacetedUniqueValues(),
    paginatedRowModel: createPaginatedRowModel(),
    sortedRowModel: createSortedRowModel(),
    filterFns,
    sortFns,
});

export type DataTableFeatures = typeof dataTableFeatures;

export type DataTableColumnDef<TData extends RowData, TValue extends CellData = CellData> =
    ColumnDef<DataTableFeatures, TData, TValue>;

export type DataTableInstance<TData extends RowData> = ReactTable<DataTableFeatures, TData>;

interface DataTableProps<TData extends RowData> {
    columns: DataTableColumnDef<TData>[];
    data: TData[];
    mobileCardRenderer?: (row: TData) => React.ReactNode;
    onRowSelectionChange?: (selectedRows: TData[]) => void;
    children?: (props: { table: DataTableInstance<TData> }) => React.ReactNode;
    initialColumnFilters?: ColumnFiltersState;
}

export function DataTable<TData extends RowData>({
    columns,
    data,
    mobileCardRenderer,
    onRowSelectionChange,
    children,
    initialColumnFilters,
}: DataTableProps<TData>) {
    const [sorting, setSorting] = useState<SortingState>([]);
    const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>(initialColumnFilters || []);
    const [columnVisibility, setColumnVisibility] = useState<ColumnVisibilityState>({});
    const [rowSelection, setRowSelection] = useState<RowSelectionState>({});

    // Sync external filters
    useEffect(() => {
        if (initialColumnFilters && JSON.stringify(initialColumnFilters) !== JSON.stringify(columnFilters)) {
            setColumnFilters(initialColumnFilters);
        }
    }, [initialColumnFilters]);

    const table = useTable({
        features: dataTableFeatures,
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

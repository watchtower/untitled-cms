import { Table } from "@tanstack/react-table"
import { X } from "lucide-react"

import { Button } from "@/Components/ui/button"
import { Input } from "@/Components/ui/input"
import { DataTableViewOptions } from "./DataTableViewOptions"

import { DataTableFacetedFilter } from "./DataTableFacetedFilter"

interface DataTableToolbarProps<TData> {
    table: Table<TData>
    searchKey: string
    filters?: {
        column: string
        title: string
        options: {
            label: string
            value: string
            icon?: React.ComponentType<{ className?: string }>
        }[]
    }[]
    children?: React.ReactNode
}

export function DataTableToolbar<TData>({
    table,
    searchKey,
    filters,
    children,
}: DataTableToolbarProps<TData>) {
    const isFiltered = table.getState().columnFilters.length > 0

    return (
        <div className="flex items-center justify-between">
            <div className="flex flex-1 items-center space-x-2">
                <Input
                    placeholder="Filter..."
                    value={(table.getColumn(searchKey)?.getFilterValue() as string) ?? ""}
                    onChange={(event) =>
                        table.getColumn(searchKey)?.setFilterValue(event.target.value)
                    }
                    className="h-8 w-[150px] lg:w-[250px]"
                />
                {filters?.map(
                    (filter) =>
                        table.getColumn(filter.column) && (
                            <DataTableFacetedFilter
                                key={filter.column}
                                column={table.getColumn(filter.column)}
                                title={filter.title}
                                options={filter.options}
                            />
                        )
                )}
                {isFiltered && (
                    <Button
                        variant="ghost"
                        onClick={() => table.resetColumnFilters()}
                        className="h-8 px-2 lg:px-3"
                    >
                        Reset
                        <X className="ml-2 h-4 w-4" />
                    </Button>
                )}
            </div>
            <div className="flex items-center space-x-2">
                {children}
                <DataTableViewOptions table={table} />
            </div>
        </div>
    )
}

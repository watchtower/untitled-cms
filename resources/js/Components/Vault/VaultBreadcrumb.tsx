import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/Components/ui/breadcrumb';
import { VaultFolder } from '@/types/vault';
import { Home } from 'lucide-react';

interface VaultBreadcrumbProps {
    folder: VaultFolder | null;
    ancestors: VaultFolder[]; // List of parent folders up to root
    onNavigate: (folderId: string | null) => void;
}

export default function VaultBreadcrumb({ folder, ancestors, onNavigate }: VaultBreadcrumbProps) {
    return (
        <Breadcrumb>
            <BreadcrumbList>
                <BreadcrumbItem>
                    <BreadcrumbLink
                        href="#"
                        onClick={(e) => { e.preventDefault(); onNavigate(null); }}
                        className="flex items-center gap-1"
                    >
                        <Home className="h-4 w-4" />
                        <span className="sr-only">Home</span>
                    </BreadcrumbLink>
                </BreadcrumbItem>

                {ancestors.map((ancestor) => (
                    <div key={ancestor.id} className="flex items-center">
                        <BreadcrumbSeparator />
                        <BreadcrumbItem>
                            <BreadcrumbLink
                                href="#"
                                onClick={(e) => { e.preventDefault(); onNavigate(ancestor.id); }}
                            >
                                {ancestor.name}
                            </BreadcrumbLink>
                        </BreadcrumbItem>
                    </div>
                ))}

                {folder && (
                    <div className="flex items-center">
                        <BreadcrumbSeparator />
                        <BreadcrumbItem>
                            <BreadcrumbPage>{folder.name}</BreadcrumbPage>
                        </BreadcrumbItem>
                    </div>
                )}
            </BreadcrumbList>
        </Breadcrumb>
    );
}

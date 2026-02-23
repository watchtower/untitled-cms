import { useState, useEffect, useCallback } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import VaultUploadDialog from '@/Components/Vault/VaultUploadDialog';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import {
    ResizableHandle,
    ResizablePanel,
    ResizablePanelGroup,
} from '@/Components/ui/resizable';
import { ScrollArea } from '@/Components/ui/scroll-area';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/Components/ui/popover';
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuTrigger,
} from '@/Components/ui/context-menu';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import { VaultFile, VaultFolder } from '@/types/vault';
import VaultThumbnail from '@/Components/Vault/VaultThumbnail';
import VaultBreadcrumb from '@/Components/Vault/VaultBreadcrumb';
import VaultFileIcon from '@/Components/Vault/VaultFileIcon';
import {
    Folder,
    FolderPlus,
    Search,
    Upload,
    MoreVertical,
    FileIcon,
    Info,
    Trash2,
    Download,
    Pencil,
    Move,
    LayoutGrid,
    List,
    Check,
    Sparkles,
    ImagePlus,
    Wand2,
    Loader2
} from 'lucide-react';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';

// Info Popover Component
function VaultFolderInfoPopover({ folder, side = "right" }: { folder: VaultFolder, side?: "left" | "right" | "top" | "bottom" }) {
    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button variant="ghost" size="icon" className={side === "right" ? "h-6 w-6 rounded-full hover:bg-background/80" : "h-8 w-8 rounded-full"}>
                    <Info className="h-4 w-4 text-muted-foreground" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-56 z-[100]" side={side} align={side === "right" ? "start" : "center"} onClick={(e: React.MouseEvent) => e.stopPropagation()}>
                <div className="space-y-2">
                    <h4 className="font-semibold text-sm break-all" title={folder.name}>{folder.name}</h4>
                    <div className="grid grid-cols-2 gap-1 text-sm text-muted-foreground">
                        <span>Total Items:</span>
                        <span className="text-foreground text-right">{folder.files_count || 0}</span>
                        <span>Total Size:</span>
                        <span className="text-foreground text-right">{((folder.files_size || 0) / 1024).toFixed(1)} KB</span>
                        <span>Access:</span>
                        <span className="text-foreground text-right">{folder.is_restricted ? 'Restricted' : 'Global'}</span>
                        <span>Owned By:</span>
                        <span className="text-foreground text-right truncate" title={folder.owner?.name}>{folder.owner?.name || 'Unknown'}</span>
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    );
}

export default function VaultIndex({ maxUploadSize = 2, phpIniPath = '' }: { maxUploadSize?: number, phpIniPath?: string }) {
    const [folders, setFolders] = useState<VaultFolder[]>([]);
    const [files, setFiles] = useState<VaultFile[]>([]);
    const [currentFolder, setCurrentFolder] = useState<VaultFolder | null>(null);
    const [ancestors, setAncestors] = useState<VaultFolder[]>([]);
    const [selectedFiles, setSelectedFiles] = useState<VaultFile[]>([]);
    const [lastSelectedFile, setLastSelectedFile] = useState<VaultFile | null>(null);
    const [search, setSearch] = useState('');
    const [loading, setLoading] = useState(false);
    const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');

    // Persistence
    useEffect(() => {
        const savedView = localStorage.getItem('vaultViewMode');
        if (savedView === 'grid' || savedView === 'list') {
            setViewMode(savedView);
        }
    }, []);

    useEffect(() => {
        localStorage.setItem('vaultViewMode', viewMode);
    }, [viewMode]);

    // Dialogs
    const [isCreateFolderOpen, setIsCreateFolderOpen] = useState(false);
    const [newFolderName, setNewFolderName] = useState('');
    const [isRenameOpen, setIsRenameOpen] = useState(false);
    const [renameName, setRenameName] = useState('');
    const [renameTarget, setRenameTarget] = useState<'file' | 'folder' | null>(null);
    const [isUploadOpen, setIsUploadOpen] = useState(false);

    // AI Features State
    const [isGeneratingAlt, setIsGeneratingAlt] = useState(false);
    const [generatedAltText, setGeneratedAltText] = useState<string | null>(null);
    const [isImageGenOpen, setIsImageGenOpen] = useState(false);
    const [imageGenPrompt, setImageGenPrompt] = useState('');
    const [isGeneratingImage, setIsGeneratingImage] = useState(false);
    const [generatedImageUrl, setGeneratedImageUrl] = useState<string | null>(null);

    const handleGenerateAltText = async (fileUuid: string) => {
        setIsGeneratingAlt(true);
        setGeneratedAltText(null);
        try {
            const response = await axios.post('/ai/generate-alt-text', { vault_file_uuid: fileUuid });
            setGeneratedAltText(response.data.alt_text);
            toast.success('Alt text generated!');
        } catch (e: any) {
            toast.error(e.response?.data?.error || 'Failed to generate alt text. Check your active AI Hub.');
        } finally {
            setIsGeneratingAlt(false);
        }
    };

    // Move dialog state
    const [isMoveOpen, setIsMoveOpen] = useState(false);
    const [moveTarget, setMoveTarget] = useState<string | null>(null); // selected folder id

    const [isSavingImage, setIsSavingImage] = useState(false);

    const handleMove = async () => {
        if (!selectedFiles.length) return;
        try {
            await Promise.all(
                selectedFiles.map(f =>
                    axios.patch(route('vault.file.move', f.uuid), { folder_id: moveTarget || null })
                )
            );
            const dest = moveTarget ? folders.find(f => f.id === moveTarget)?.name || 'folder' : 'Root';
            toast.success(`Moved ${selectedFiles.length} item(s) to ${dest}`);
            setIsMoveOpen(false);
            setSelectedFiles([]);
            setMoveTarget(null);
            refresh(currentFolder?.id || null);
        } catch (e: any) {
            toast.error(e.response?.data?.message || 'Move failed.');
        }
    };

    const handleGenerateImage = async () => {
        if (!imageGenPrompt.trim()) return;
        setIsGeneratingImage(true);
        setGeneratedImageUrl(null);
        try {
            // Step 1: Generate the image
            const genResponse = await axios.post('/ai/generate-image', { prompt: imageGenPrompt });
            const imageData = genResponse.data.image_url;
            setGeneratedImageUrl(imageData);
            toast.success('Image generated — saving to Vault...');

            // Step 2: Auto-save to vault at the current folder
            setIsSavingImage(true);
            const saveResponse = await axios.post('/vault/save-ai-image', {
                image: imageData,
                folder_id: currentFolder?.id || null,
                filename: `ai-${imageGenPrompt.trim().slice(0, 40).replace(/[^a-zA-Z0-9]/g, '-')}`,
            });

            toast.success(`Saved to Vault: ${saveResponse.data.file?.original_name || 'ai-generated.png'}`);

            // Step 3: Close dialog and refresh vault
            setIsImageGenOpen(false);
            setImageGenPrompt('');
            setGeneratedImageUrl(null);
            refresh(currentFolder?.id || null);
        } catch (e: any) {
            toast.error(e.response?.data?.error || 'Generation or save failed.');
        } finally {
            setIsGeneratingImage(false);
            setIsSavingImage(false);
        }
    };

    // Trash Support
    const [isTrashView, setIsTrashView] = useState(false);

    // Initial Load
    const refresh = useCallback((folderId: string | null = null, trashTarget: boolean = isTrashView) => {
        setLoading(true);
        const params: any = { folder_id: folderId, search };
        const endpoint = trashTarget ? route('vault.trash.list') : route('vault.files.list');

        Promise.all([
            axios.get(endpoint, { params }),
            axios.get(route('vault.folders.list'))
        ]).then(([filesRes, foldersRes]) => {
            setFiles(filesRes.data.data || filesRes.data);
            setFolders(foldersRes.data); // All folders for tree? Or just current level?
            // Assuming folders.list returns ALL folders for tree structure construction

            if (folderId) {
                const current = foldersRes.data.find((f: any) => f.id === folderId);
                setCurrentFolder(current || null);
                // Rebuild ancestors
                const trail: VaultFolder[] = [];
                let curr = current;
                while (curr && curr.parent_id) {
                    const parent = foldersRes.data.find((f: any) => f.id === curr.parent_id);
                    if (parent) {
                        trail.unshift(parent);
                        curr = parent;
                    } else {
                        break;
                    }
                }
                setAncestors(trail);
            } else {
                setCurrentFolder(null);
                setAncestors([]);
            }
            setSelectedFiles([]);
            setLastSelectedFile(null);
        }).catch(err => {
            console.error(err);
            toast.error('Failed to load vault data');
        }).finally(() => {
            setLoading(false);
        });
    }, [search]);

    useEffect(() => {
        refresh(currentFolder?.id || null);
    }, [refresh, currentFolder?.id]);

    // Actions
    const handleCreateFolder = async () => {
        if (!newFolderName) return;
        try {
            await axios.post(route('vault.folders.store'), {
                name: newFolderName,
                parent_id: currentFolder?.id
            });
            toast.success('Folder created');
            setIsCreateFolderOpen(false);
            setNewFolderName('');
            refresh(currentFolder?.id || null);
        } catch (error) {
            toast.error('Failed to create folder');
        }
    };

    const handleDelete = async (type: 'file' | 'folder', id: string) => {
        if (!confirm('Are you sure you want to delete this item?')) return;
        try {
            const routeName = type === 'file' ? 'vault.file.destroy' : 'vault.folders.destroy';
            await axios.delete(route(routeName, id));
            toast.success(`${type === 'file' ? 'File' : 'Folder'} deleted`);
            if (type === 'file') setSelectedFiles(prev => prev.filter(f => f.uuid !== id));
            refresh(currentFolder?.id || null);
        } catch (error) {
            toast.error('Failed to delete item');
        }
    };

    const handleRestore = async (type: 'file' | 'folder', id: string) => {
        try {
            const routeName = type === 'file' ? 'vault.file.restore' : 'vault.folders.restore';
            await axios.post(route(routeName, id));
            toast.success(`${type === 'file' ? 'File' : 'Folder'} restored`);
            if (type === 'file') setSelectedFiles(prev => prev.filter(f => f.uuid !== id));
            refresh(currentFolder?.id || null);
        } catch (error) {
            toast.error('Failed to restore item');
        }
    };

    const handleForceDelete = async (type: 'file' | 'folder', id: string) => {
        if (!confirm('Are you sure you want to PERMANENTLY delete this item? This cannot be undone.')) return;
        try {
            const routeName = type === 'file' ? 'vault.file.force_destroy' : 'vault.folders.force_destroy';
            await axios.delete(route(routeName, id));
            toast.success(`${type === 'file' ? 'File' : 'Folder'} permanently deleted`);
            if (type === 'file') setSelectedFiles(prev => prev.filter(f => f.uuid !== id));
            refresh(currentFolder?.id || null);
        } catch (error) {
            toast.error('Failed to permanently delete item');
        }
    };

    const handleRename = async () => {
        if (!renameTarget || (selectedFiles.length === 0 && renameTarget === 'file')) return;
        const id = renameTarget === 'file' ? selectedFiles[0]?.uuid : currentFolder?.id; // Simplify for now
    };

    const handleSelectFile = (e: React.MouseEvent, file: VaultFile) => {
        e.stopPropagation();
        if (e.shiftKey && lastSelectedFile) {
            const startIdx = files.findIndex(f => f.id === lastSelectedFile.id);
            const endIdx = files.findIndex(f => f.id === file.id);
            if (startIdx !== -1 && endIdx !== -1) {
                const min = Math.min(startIdx, endIdx);
                const max = Math.max(startIdx, endIdx);
                const range = files.slice(min, max + 1);

                if (e.metaKey || e.ctrlKey) {
                    const newSelection = [...selectedFiles];
                    range.forEach(r => {
                        if (!newSelection.find(s => s.id === r.id)) newSelection.push(r);
                    });
                    setSelectedFiles(newSelection);
                } else {
                    setSelectedFiles(range);
                }
            }
        } else if (e.metaKey || e.ctrlKey) {
            const isSelected = selectedFiles.some(f => f.id === file.id);
            if (isSelected) {
                setSelectedFiles(selectedFiles.filter(f => f.id !== file.id));
            } else {
                setSelectedFiles([...selectedFiles, file]);
            }
            setLastSelectedFile(file);
        } else {
            setSelectedFiles([file]);
            setLastSelectedFile(file);
        }
    };

    // Helper to render folder tree (recursive)
    const renderFolderTree = (parentId: string | null = null, depth = 0) => {
        const children = folders.filter(f => f.parent_id === parentId);
        return children.map(folder => (
            <div key={folder.id}>
                <div
                    className={cn(
                        "flex items-center gap-2 px-2 py-1.5 hover:bg-accent rounded-sm cursor-pointer text-sm",
                        !isTrashView && currentFolder?.id === folder.id && "bg-accent text-accent-foreground font-medium"
                    )}
                    style={{ paddingLeft: `${depth * 12 + 8}px` }}
                    onClick={() => {
                        setIsTrashView(false);
                        setCurrentFolder(folder);
                    }}
                >
                    <Folder className="h-4 w-4 shrink-0 fill-current text-blue-500/80" />
                    <span className="truncate">{folder.name}</span>
                </div>
                {renderFolderTree(folder.id, depth + 1)}
            </div>
        ));
    };

    // const handleFileUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    //     if (!e.target.files?.length) return;
    //     const formData = new FormData();
    //     Array.from(e.target.files).forEach(file => {
    //         formData.append('files[]', file);
    //     });
    //     if (currentFolder) formData.append('folder_id', currentFolder.id);

    //     const toastId = toast.loading('Uploading...');
    //     try {
    //         const response = await axios.post(route('vault.upload'), formData);
    //         const { uploaded, errors } = response.data;

    //         toast.dismiss(toastId);

    //         if (uploaded.length > 0) {
    //             toast.success(`${uploaded.length} file(s) uploaded`);
    //             refresh(currentFolder?.id || null);
    //         }

    //         if (errors && errors.length > 0) {
    //             const msg = errors.length === 1 ? errors[0].error : `${errors.length} files failed to upload`;
    //             toast.warning('Upload Issue', { description: msg });
    //             console.error('Upload errors:', errors);
    //         }
    //     } catch (error: any) {
    //         toast.dismiss(toastId);
    //         if (error.response?.status === 413) {
    //             toast.error('File is too large', {
    //                 description: `Limit is ${maxUploadSize}MB. Check upload_max_filesize in ${phpIniPath || 'php.ini'}.`
    //             });
    //         } else if (error.response?.status === 422) {
    //             toast.error('Validation failed', {
    //                 description: error.response.data.message || 'Please checks your input.'
    //             });
    //         } else {
    //             toast.error('Upload failed', {
    //                 description: error.message || 'An unexpected error occurred.'
    //             });
    //         }
    //         console.error(error);
    //     }
    // };

    return (
        <AuthenticatedLayout header="Media Vault">
            <Head title="Media Vault" />

            <div className="flex flex-col flex-1 min-h-0 w-full max-w-full rounded-xl border bg-card shadow-sm overflow-hidden">
                {/* Toolbar */}
                <div className="flex items-center justify-between p-4 border-b shrink-0">
                    <div className="flex items-center gap-2">
                        <Button onClick={() => setIsCreateFolderOpen(true)} variant="outline" size="sm" className="gap-2">
                            <FolderPlus className="h-4 w-4" />
                            New Folder
                        </Button>
                        <Button
                            onClick={() => setIsUploadOpen(true)}
                            variant="default"
                            size="sm"
                            className="gap-2"
                        >
                            <Upload className="h-4 w-4" />
                            Upload Files
                        </Button>
                        <Button
                            onClick={() => { setIsImageGenOpen(true); setGeneratedImageUrl(null); setImageGenPrompt(''); }}
                            variant="outline"
                            size="sm"
                            className="gap-2 border-primary/30 text-primary hover:bg-primary/5"
                        >
                            <Wand2 className="h-4 w-4" />
                            AI Generate Image
                        </Button>
                    </div>

                    <div className="relative">
                        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            type="search"
                            placeholder="Search files..."
                            className="pl-8 w-[200px] lg:w-[300px]"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </div>
                </div>

                <ResizablePanelGroup id="vault-panel-group" orientation="horizontal" className="flex-1 h-full w-full max-w-full">
                    {/* Left Sidebar (Folder Tree) */}
                    <ResizablePanel id="vault-sidebar" defaultSize="20" minSize="10" maxSize="50" className="bg-muted/10">
                        <div className="h-full min-w-0 flex flex-col">
                            <ScrollArea className="flex-1">
                                <div className="p-2 space-y-1">
                                    <div
                                        className={cn(
                                            "flex items-center gap-2 px-2 py-1.5 hover:bg-accent rounded-sm cursor-pointer text-sm",
                                            !isTrashView && currentFolder === null && "bg-accent text-accent-foreground font-medium"
                                        )}
                                        onClick={() => {
                                            setIsTrashView(false);
                                            setCurrentFolder(null);
                                            refresh(null, false);
                                        }}
                                    >
                                        <Folder className="h-4 w-4 shrink-0 fill-current text-blue-500/80" />
                                        <span>All Files</span>
                                    </div>
                                    <div className="pt-2">
                                        {renderFolderTree(null, 0)}
                                    </div>
                                </div>
                            </ScrollArea>

                            {/* Trash Pinned to Bottom */}
                            <div className="p-2 border-t mt-auto shrink-0 bg-muted/10">
                                <div
                                    className={cn(
                                        "flex items-center gap-2 px-2 py-1.5 hover:bg-accent rounded-sm cursor-pointer text-sm",
                                        isTrashView && "bg-accent text-accent-foreground font-medium text-red-600"
                                    )}
                                    onClick={() => {
                                        setIsTrashView(true);
                                        setCurrentFolder(null); // Clear folder selection in trash view
                                        refresh(null, true);
                                    }}
                                >
                                    <Trash2 className="h-4 w-4 shrink-0 fill-current text-red-500/80" />
                                    <span>Trash</span>
                                </div>
                            </div>
                        </div>
                    </ResizablePanel>

                    <ResizableHandle />

                    {/* Main Content */}
                    <ResizablePanel id="vault-main" defaultSize="55">
                        <div className="flex flex-col h-full min-w-0">
                            <div className="px-4 py-2 border-b bg-muted/20 flex items-center justify-between">
                                <div className="flex-1 flex items-center min-w-0">
                                    {isTrashView ? (
                                        <h2 className="text-sm font-semibold text-red-600 flex items-center gap-2">
                                            <Trash2 className="h-4 w-4" /> Trash
                                        </h2>
                                    ) : (
                                        <VaultBreadcrumb
                                            folder={currentFolder}
                                            ancestors={ancestors}
                                            onNavigate={(id) => {
                                                setIsTrashView(false);
                                                setCurrentFolder(folders.find(f => f.id === id) || null);
                                            }}
                                        />
                                    )}
                                </div>
                                <div className="flex items-center gap-1 bg-background border rounded-md p-0.5 ml-4 shrink-0 shadow-sm">
                                    <Button
                                        variant={viewMode === 'grid' ? 'secondary' : 'ghost'}
                                        size="sm"
                                        className="h-7 px-2"
                                        onClick={() => setViewMode('grid')}
                                    >
                                        <LayoutGrid className="h-4 w-4" />
                                    </Button>
                                    <Button
                                        variant={viewMode === 'list' ? 'secondary' : 'ghost'}
                                        size="sm"
                                        className="h-7 px-2"
                                        onClick={() => setViewMode('list')}
                                    >
                                        <List className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>

                            <ScrollArea className="flex-1 p-4">
                                {loading ? (
                                    <div className="py-20 text-center text-muted-foreground">Loading...</div>
                                ) : viewMode === 'grid' ? (
                                    <div className="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                        {/* Current Level Folders */}
                                        {!isTrashView && folders.filter(f => f.parent_id === (currentFolder?.id || null)).map(folder => (
                                            <ContextMenu key={folder.id}>
                                                <ContextMenuTrigger>
                                                    <div
                                                        className="group relative flex flex-col items-center gap-2 p-4 border rounded-lg hover:bg-accent cursor-pointer transition-colors"
                                                        onClick={() => setCurrentFolder(folder)}
                                                    >
                                                        <div className="absolute top-2 right-2 z-10" onClick={(e: React.MouseEvent) => e.stopPropagation()}>
                                                            <VaultFolderInfoPopover folder={folder} side="right" />
                                                        </div>
                                                        <div className="aspect-square w-full relative overflow-hidden flex items-center justify-center">
                                                            <Folder className="h-16 w-16 fill-blue-100 text-blue-500" />
                                                        </div>
                                                        <span className="text-sm font-medium truncate w-full text-center" title={folder.name}>{folder.name}</span>
                                                    </div>
                                                </ContextMenuTrigger>
                                                <ContextMenuContent>
                                                    <ContextMenuItem onClick={() => { setCurrentFolder(folder); }}>Open</ContextMenuItem>
                                                    <ContextMenuItem onClick={() => handleDelete('folder', folder.id)} className="text-red-600">Delete</ContextMenuItem>
                                                </ContextMenuContent>
                                            </ContextMenu>
                                        ))}

                                        {/* Files */}
                                        {files.map(file => (
                                            <ContextMenu key={file.id}>
                                                <ContextMenuTrigger>
                                                    <VaultThumbnail
                                                        file={file}
                                                        selected={selectedFiles.some(f => f.id === file.id)}
                                                        onSelect={(e) => handleSelectFile(e, file)}
                                                        onDoubleClick={() => window.open(route('vault.file.serve', file.uuid), '_blank')}
                                                    />
                                                </ContextMenuTrigger>
                                                <ContextMenuContent>
                                                    {isTrashView ? (
                                                        <>
                                                            <ContextMenuItem onClick={() => handleRestore('file', file.uuid)} className="text-green-600">
                                                                Restore
                                                            </ContextMenuItem>
                                                            <ContextMenuSeparator />
                                                            <ContextMenuItem onClick={() => handleForceDelete('file', file.uuid)} className="text-red-600">
                                                                <Trash2 className="mr-2 h-4 w-4" /> Delete Permanently
                                                            </ContextMenuItem>
                                                        </>
                                                    ) : (
                                                        <>
                                                            <ContextMenuItem onClick={() => window.open(route('vault.file.serve', file.uuid), '_blank')}>
                                                                <Download className="mr-2 h-4 w-4" /> Download/View
                                                            </ContextMenuItem>
                                                            <ContextMenuSeparator />
                                                            <ContextMenuItem onClick={() => handleDelete('file', file.uuid)} className="text-red-600">
                                                                <Trash2 className="mr-2 h-4 w-4" /> Delete
                                                            </ContextMenuItem>
                                                        </>
                                                    )}
                                                </ContextMenuContent>
                                            </ContextMenu>
                                        ))}

                                        {!loading && files.length === 0 && folders.filter(f => f.parent_id === (currentFolder?.id || null)).length === 0 && (
                                            <div className="col-span-full py-20 flex flex-col items-center justify-center text-muted-foreground">
                                                <Folder className="h-12 w-12 mb-4 opacity-10" />
                                                <p>No items in this folder</p>
                                            </div>
                                        )}
                                    </div>
                                ) : (
                                    <div className="flex flex-col border rounded-md divide-y bg-background shadow-sm">
                                        {/* Current Level Folders */}
                                        {!isTrashView && folders.filter(f => f.parent_id === (currentFolder?.id || null)).map(folder => (
                                            <ContextMenu key={folder.id}>
                                                <ContextMenuTrigger>
                                                    <div
                                                        className="flex items-center gap-4 p-3 hover:bg-accent cursor-pointer transition-colors"
                                                        onClick={() => setCurrentFolder(folder)}
                                                    >
                                                        <Folder className="h-8 w-8 fill-blue-100 text-blue-500 shrink-0" />
                                                        <span className="text-sm font-medium flex-1 truncate">{folder.name}</span>
                                                        <div className="shrink-0" onClick={(e: React.MouseEvent) => e.stopPropagation()}>
                                                            <VaultFolderInfoPopover folder={folder} side="left" />
                                                        </div>
                                                    </div>
                                                </ContextMenuTrigger>
                                                <ContextMenuContent>
                                                    <ContextMenuItem onClick={() => { setCurrentFolder(folder); }}>Open</ContextMenuItem>
                                                    <ContextMenuItem onClick={() => handleDelete('folder', folder.id)} className="text-red-600">Delete</ContextMenuItem>
                                                </ContextMenuContent>
                                            </ContextMenu>
                                        ))}

                                        {/* Files */}
                                        {files.map(file => (
                                            <ContextMenu key={file.id}>
                                                <ContextMenuTrigger>
                                                    <div
                                                        className={cn(
                                                            "group relative flex items-center gap-4 p-3 hover:bg-accent cursor-pointer transition-colors",
                                                            selectedFiles.some(f => f.id === file.id) && "bg-accent/50"
                                                        )}
                                                        onClick={(e) => handleSelectFile(e, file)}
                                                        onDoubleClick={() => window.open(route('vault.file.serve', file.uuid), '_blank')}
                                                    >
                                                        <div className="relative h-10 w-10 shrink-0 bg-muted/20 border rounded flex items-center justify-center overflow-hidden">
                                                            {file.mime_type.startsWith('image/') && file.url ? (
                                                                <img src={file.url} alt={file.original_name} className="object-cover w-full h-full" />
                                                            ) : (
                                                                <VaultFileIcon mimeType={file.mime_type} className="h-6 w-6 text-muted-foreground" />
                                                            )}
                                                            {selectedFiles.some(f => f.id === file.id) && (
                                                                <div className="absolute inset-0 bg-primary/20 flex items-center justify-center">
                                                                    <div className="bg-primary text-primary-foreground rounded-full p-0.5 shadow-sm">
                                                                        <Check className="h-4 w-4" />
                                                                    </div>
                                                                </div>
                                                            )}
                                                        </div>
                                                        <div className="flex-1 min-w-0 flex flex-col justify-center">
                                                            <span className="text-sm font-medium truncate">{file.original_name}</span>
                                                        </div>
                                                        <div className="w-24 shrink-0 text-xs text-muted-foreground text-right hidden sm:block">
                                                            {(file.size_bytes / 1024).toFixed(1)} KB
                                                        </div>
                                                        <div className="w-32 shrink-0 text-xs text-muted-foreground text-right hidden md:block">
                                                            {new Date(file.created_at).toLocaleDateString()}
                                                        </div>
                                                    </div>
                                                </ContextMenuTrigger>
                                                <ContextMenuContent>
                                                    {isTrashView ? (
                                                        <>
                                                            <ContextMenuItem onClick={() => handleRestore('file', file.uuid)} className="text-green-600">
                                                                Restore
                                                            </ContextMenuItem>
                                                            <ContextMenuSeparator />
                                                            <ContextMenuItem onClick={() => handleForceDelete('file', file.uuid)} className="text-red-600">
                                                                <Trash2 className="mr-2 h-4 w-4" /> Delete Permanently
                                                            </ContextMenuItem>
                                                        </>
                                                    ) : (
                                                        <>
                                                            <ContextMenuItem onClick={() => window.open(route('vault.file.serve', file.uuid), '_blank')}>
                                                                <Download className="mr-2 h-4 w-4" /> Download/View
                                                            </ContextMenuItem>
                                                            <ContextMenuSeparator />
                                                            <ContextMenuItem onClick={() => handleDelete('file', file.uuid)} className="text-red-600">
                                                                <Trash2 className="mr-2 h-4 w-4" /> Delete
                                                            </ContextMenuItem>
                                                        </>
                                                    )}
                                                </ContextMenuContent>
                                            </ContextMenu>
                                        ))}

                                        {!loading && files.length === 0 && folders.filter(f => f.parent_id === (currentFolder?.id || null)).length === 0 && (
                                            <div className="py-12 flex flex-col items-center justify-center text-muted-foreground">
                                                <Folder className="h-8 w-8 mb-2 opacity-10" />
                                                <p className="text-sm">No items in this folder</p>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </ScrollArea>
                        </div>
                    </ResizablePanel>

                    <ResizableHandle />

                    {/* Inspector */}
                    <ResizablePanel id="vault-inspector" defaultSize="25" minSize="15" maxSize="50" className="bg-muted/5">
                        <ScrollArea className="h-full">
                            {selectedFiles.length > 0 ? (
                                <div className="p-6 space-y-6 text-sm">
                                    {selectedFiles.length === 1 ? (
                                        <>
                                            <div className="aspect-square w-full rounded-lg border bg-background flex items-center justify-center overflow-hidden">
                                                {selectedFiles[0].mime_type.startsWith('image/') ? (
                                                    <img src={selectedFiles[0].url} alt={selectedFiles[0].original_name} className="object-contain w-full h-full" />
                                                ) : (
                                                    <VaultFileIcon mimeType={selectedFiles[0].mime_type} className="h-24 w-24 text-muted-foreground" />
                                                )}
                                            </div>

                                            <div className="space-y-1">
                                                <h3 className="font-semibold text-lg break-words">{selectedFiles[0].original_name}</h3>
                                                <p className="text-sm text-muted-foreground uppercase">{selectedFiles[0].extension}</p>
                                            </div>

                                            {/* AI Alt-Text Generator — only for images */}
                                            {selectedFiles[0].mime_type.startsWith('image/') && (() => {
                                                const currentAlt = generatedAltText ?? (selectedFiles[0].alt_text || '');
                                                return (
                                                    <div className="space-y-2 bg-primary/5 border border-primary/20 rounded-lg p-3">
                                                        <div className="flex items-center justify-between">
                                                            <div className="flex items-center gap-2">
                                                                <Sparkles className="h-4 w-4 text-primary" />
                                                                <span className="text-xs font-medium text-primary">Alt Text</span>
                                                            </div>
                                                        </div>
                                                        <Textarea
                                                            className="text-xs min-h-[60px] resize-none"
                                                            placeholder="Describe this image for accessibility…"
                                                            value={currentAlt}
                                                            onChange={(e) => setGeneratedAltText(e.target.value)}
                                                        />
                                                        <div className="flex gap-2">
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                className="flex-1 h-7 text-xs"
                                                                disabled={isGeneratingAlt}
                                                                onClick={() => handleGenerateAltText(selectedFiles[0].uuid)}
                                                            >
                                                                {isGeneratingAlt ? (
                                                                    <><Loader2 className="mr-1 h-3 w-3 animate-spin" />Generating...</>
                                                                ) : (
                                                                    <><Sparkles className="mr-1 h-3 w-3" />AI Generate</>
                                                                )}
                                                            </Button>
                                                            <Button
                                                                size="sm"
                                                                className="flex-1 h-7 text-xs"
                                                                disabled={!currentAlt.trim()}
                                                                onClick={async () => {
                                                                    try {
                                                                        await axios.patch(route('vault.file.alt_text', selectedFiles[0].uuid), { alt_text: currentAlt });
                                                                        toast.success('Alt text saved!');
                                                                        refresh(currentFolder?.id || null);
                                                                    } catch {
                                                                        toast.error('Failed to save alt text.');
                                                                    }
                                                                }}
                                                            >
                                                                Save
                                                            </Button>
                                                        </div>
                                                    </div>
                                                );
                                            })()}


                                            <div className="grid gap-4 py-4 border-y text-sm">
                                                <div className="grid grid-cols-2">
                                                    <span className="text-muted-foreground">Size</span>
                                                    <span>{(selectedFiles[0].size_bytes / 1024).toFixed(1)} KB</span>
                                                </div>
                                                <div className="grid grid-cols-2">
                                                    <span className="text-muted-foreground">Type</span>
                                                    <span className="truncate" title={selectedFiles[0].mime_type}>{selectedFiles[0].mime_type}</span>
                                                </div>
                                                <div className="grid grid-cols-2">
                                                    <span className="text-muted-foreground">Uploaded</span>
                                                    <span>{new Date(selectedFiles[0].created_at).toLocaleDateString()}</span>
                                                </div>
                                                {selectedFiles[0].width && (
                                                    <div className="grid grid-cols-2">
                                                        <span className="text-muted-foreground">Dimensions</span>
                                                        <span>{selectedFiles[0].width} x {selectedFiles[0].height}</span>
                                                    </div>
                                                )}
                                            </div>
                                        </>
                                    ) : (
                                        <>
                                            <div className="aspect-square w-full rounded-lg border bg-muted/20 flex flex-col items-center justify-center text-muted-foreground">
                                                <FileIcon className="h-16 w-16 mb-2" />
                                                <span className="font-medium">{selectedFiles.length} items selected</span>
                                            </div>
                                            <div className="grid gap-4 py-4 border-y text-sm">
                                                <div className="grid grid-cols-2">
                                                    <span className="text-muted-foreground">Total Size</span>
                                                    <span>{(selectedFiles.reduce((acc, f) => acc + f.size_bytes, 0) / 1024 / 1024).toFixed(2)} MB</span>
                                                </div>
                                            </div>
                                        </>
                                    )}

                                    <div className="flex flex-col gap-2">
                                        {isTrashView ? (
                                            <>
                                                <Button
                                                    variant="outline"
                                                    onClick={() => Promise.all(selectedFiles.map(f => handleRestore('file', f.uuid)))}
                                                    className="w-full text-green-600 border-green-200 hover:bg-green-50 hover:text-green-700"
                                                >
                                                    Restore {selectedFiles.length > 1 && 'All'}
                                                </Button>
                                                <Button
                                                    variant="destructive"
                                                    onClick={() => {
                                                        if (confirm(`Are you sure you want to PERMANENTLY delete these ${selectedFiles.length} items?`)) {
                                                            Promise.all(selectedFiles.map(f => axios.delete(route('vault.file.force_destroy', f.uuid))))
                                                                .then(() => {
                                                                    toast.success(`${selectedFiles.length} items permanently deleted`);
                                                                    setSelectedFiles([]);
                                                                    refresh(currentFolder?.id || null);
                                                                });
                                                        }
                                                    }}
                                                    className="w-full"
                                                >
                                                    <Trash2 className="mr-2 h-4 w-4" /> Delete Permanently
                                                </Button>
                                            </>
                                        ) : (
                                            <>
                                                {selectedFiles.length === 1 && (
                                                    <Button variant="outline" onClick={() => window.open(route('vault.file.serve', selectedFiles[0].uuid), '_blank')} className="w-full">
                                                        <Download className="mr-2 h-4 w-4" /> Download
                                                    </Button>
                                                )}
                                                <Button
                                                    variant="outline"
                                                    className="w-full"
                                                    onClick={() => { setMoveTarget(null); setIsMoveOpen(true); }}
                                                >
                                                    <Move className="mr-2 h-4 w-4" /> Move to Folder
                                                </Button>
                                                <Button
                                                    variant="destructive"
                                                    onClick={() => {
                                                        if (confirm(`Are you sure you want to delete these ${selectedFiles.length} items?`)) {
                                                            Promise.all(selectedFiles.map(f => axios.delete(route('vault.file.destroy', f.uuid))))
                                                                .then(() => {
                                                                    toast.success(`${selectedFiles.length} items deleted`);
                                                                    setSelectedFiles([]);
                                                                    refresh(currentFolder?.id || null);
                                                                });
                                                        }
                                                    }}
                                                    className="w-full"
                                                >
                                                    <Trash2 className="mr-2 h-4 w-4" /> Delete {selectedFiles.length > 1 && 'All'}
                                                </Button>
                                            </>
                                        )}
                                    </div>
                                </div>
                            ) : (
                                <div className="h-full flex flex-col items-center justify-center text-muted-foreground p-6 text-center">
                                    <Info className="h-12 w-12 mb-4 opacity-50" />
                                    <p>Select a file to view details</p>
                                </div>
                            )}
                        </ScrollArea>
                    </ResizablePanel>
                </ResizablePanelGroup>
            </div>

            {/* Create Folder Dialog */}
            <Dialog open={isCreateFolderOpen} onOpenChange={setIsCreateFolderOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>New Folder</DialogTitle>
                    </DialogHeader>
                    <div className="py-4">
                        <Input
                            placeholder="Folder Name"
                            value={newFolderName}
                            onChange={(e) => setNewFolderName(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleCreateFolder()}
                        />
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setIsCreateFolderOpen(false)}>Cancel</Button>
                        <Button onClick={handleCreateFolder}>Create</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <VaultUploadDialog
                open={isUploadOpen}
                onOpenChange={setIsUploadOpen}
                currentFolderId={currentFolder?.id || null}
                onUploadComplete={() => refresh(currentFolder?.id || null)}
                maxUploadSize={maxUploadSize}
            />

            {/* Move to Folder Dialog */}
            <Dialog open={isMoveOpen} onOpenChange={setIsMoveOpen}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Move className="h-5 w-5 text-primary" />
                            Move {selectedFiles.length} Item{selectedFiles.length !== 1 ? 's' : ''} to Folder
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-2 max-h-72 overflow-y-auto py-2">
                        {/* Root option */}
                        <button
                            onClick={() => setMoveTarget(null)}
                            className={cn(
                                'w-full flex items-center gap-2 px-3 py-2 rounded-md text-sm text-left transition-colors',
                                moveTarget === null
                                    ? 'bg-primary text-primary-foreground'
                                    : 'hover:bg-muted'
                            )}
                        >
                            <Folder className="h-4 w-4 flex-shrink-0" />
                            <span className="font-medium">Root</span>
                        </button>
                        {/* All folders */}
                        {folders.map(folder => (
                            <button
                                key={folder.id}
                                onClick={() => setMoveTarget(folder.id)}
                                disabled={selectedFiles.some(f => f.folder_id === folder.id)}
                                className={cn(
                                    'w-full flex items-center gap-2 px-3 py-2 rounded-md text-sm text-left transition-colors disabled:opacity-40 disabled:cursor-not-allowed',
                                    moveTarget === folder.id
                                        ? 'bg-primary text-primary-foreground'
                                        : 'hover:bg-muted'
                                )}
                            >
                                <Folder className="h-4 w-4 flex-shrink-0" />
                                {folder.name}
                            </button>
                        ))}
                        {folders.length === 0 && (
                            <p className="text-sm text-muted-foreground text-center py-4">No folders exist yet.</p>
                        )}
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setIsMoveOpen(false)}>Cancel</Button>
                        <Button onClick={handleMove}>
                            <Move className="mr-2 h-4 w-4" /> Move Here
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Text-to-Image Dialog */}
            <Dialog open={isImageGenOpen} onOpenChange={setIsImageGenOpen}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Wand2 className="h-5 w-5 text-primary" />
                            AI Image Generation
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4 py-2">
                        <div className="space-y-2">
                            <Label htmlFor="image-gen-prompt">Describe the image you want to create</Label>
                            <Textarea
                                id="image-gen-prompt"
                                placeholder="e.g. A minimalist product photo of a luxury watch on a marble surface, studio lighting, 4K..."
                                value={imageGenPrompt}
                                onChange={(e) => setImageGenPrompt(e.target.value)}
                                rows={3}
                                className="resize-none"
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter' && !e.shiftKey) {
                                        e.preventDefault();
                                        handleGenerateImage();
                                    }
                                }}
                            />
                            <p className="text-xs text-muted-foreground">
                                Uses your <strong>active AI Hub</strong> to generate an image from your description.
                                Supported providers: <strong>OpenAI</strong> (DALL-E 3), <strong>Gemini</strong> (Imagen 3), and <strong>Stability AI</strong> (SDXL).
                            </p>
                        </div>

                        {generatedImageUrl && (
                            <div className="space-y-3">
                                <div className="rounded-lg overflow-hidden border aspect-square max-h-80 flex items-center justify-center bg-muted/20">
                                    <img src={generatedImageUrl} alt="AI Generated" className="object-contain w-full h-full" />
                                </div>
                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        className="flex-1"
                                        onClick={() => window.open(generatedImageUrl, '_blank')}
                                    >
                                        <Download className="mr-2 h-4 w-4" />
                                        Open Full Size
                                    </Button>
                                    <Button
                                        variant="secondary"
                                        className="flex-1"
                                        onClick={() => { setGeneratedImageUrl(null); setImageGenPrompt(''); }}
                                    >
                                        Generate Another
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setIsImageGenOpen(false)}>Close</Button>
                        <Button
                            onClick={handleGenerateImage}
                            disabled={isGeneratingImage || isSavingImage || !imageGenPrompt.trim()}
                        >
                            {isGeneratingImage ? (
                                <><Loader2 className="mr-2 h-4 w-4 animate-spin" />Generating...</>
                            ) : isSavingImage ? (
                                <><Loader2 className="mr-2 h-4 w-4 animate-spin" />Saving to Vault...</>
                            ) : (
                                <><Sparkles className="mr-2 h-4 w-4" />Generate &amp; Save to Vault</>
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}

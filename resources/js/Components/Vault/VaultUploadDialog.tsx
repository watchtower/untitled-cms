import { useState, useCallback, useEffect } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Upload, X, AlertTriangle, Loader2 } from 'lucide-react';
import { useDropzone } from 'react-dropzone';
import axios from 'axios';
import UploadPipelineTracker, { PipelineStage } from './UploadPipelineTracker';
import { toast } from 'sonner';

interface VaultUploadDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    currentFolderId: string | null;
    onUploadComplete: () => void;
    maxUploadSize?: number; // MB
}

interface FileUploadState {
    id: string;
    file: File;
    progress: number;
    status: 'idle' | 'hashing' | 'checking' | 'ready' | 'uploading' | 'processing' | 'completed' | 'error';
    errorStage?: PipelineStage | null;
    errorMessage?: string | null;
    hash?: string;
    duplicateInfo?: any;
    forceUpload?: boolean;
}

export default function VaultUploadDialog({
    open,
    onOpenChange,
    currentFolderId,
    onUploadComplete,
    maxUploadSize = 2
}: VaultUploadDialogProps) {
    const [uploads, setUploads] = useState<FileUploadState[]>([]);
    const [isUploading, setIsUploading] = useState(false);

    const onDrop = useCallback((acceptedFiles: File[]) => {
        const newUploads = acceptedFiles.map(file => ({
            id: Math.random().toString(36).substring(7),
            file,
            progress: 0,
            status: 'idle' as const
        }));
        setUploads(prev => [...prev, ...newUploads]);
    }, []);

    const { getRootProps, getInputProps, isDragActive } = useDropzone({ onDrop });

    // Helpers
    const updateUploadState = useCallback((id: string, updates: Partial<FileUploadState>) => {
        setUploads(prev => prev.map(u => u.id === id ? { ...u, ...updates } : u));
    }, []);

    const removeUpload = (id: string) => {
        setUploads(prev => prev.filter(u => u.id !== id));
    };

    const clearCompleted = () => {
        setUploads(prev => prev.filter(u => u.status !== 'completed'));
    };

    const hashFile = async (file: File): Promise<string> => {
        const buffer = await file.arrayBuffer();
        const digest = await crypto.subtle.digest('SHA-256', buffer);
        return Array.from(new Uint8Array(digest))
            .map(b => b.toString(16).padStart(2, '0'))
            .join('');
    };

    const runHashAndCheck = useCallback(async (upload: FileUploadState) => {
        updateUploadState(upload.id, { status: 'hashing' });
        try {
            const hash = await hashFile(upload.file);
            updateUploadState(upload.id, { status: 'checking' });

            const dupResponse = await axios.get(route('admin.vault.check-duplicate'), {
                params: { hash }
            });

            if (dupResponse.data.isDuplicate && !upload.forceUpload) {
                updateUploadState(upload.id, {
                    status: 'error',
                    errorMessage: 'Duplicate detected',
                    duplicateInfo: dupResponse.data.file,
                    hash
                });
            } else {
                updateUploadState(upload.id, { status: 'ready', hash });
            }
        } catch (error) {
            updateUploadState(upload.id, { status: 'error', errorMessage: 'Hash calculation failed' });
        }
    }, [updateUploadState]);

    const processUpload = useCallback(async (upload: FileUploadState) => {
        setIsUploading(true);

        try {
            // Actual Upload
            updateUploadState(upload.id, { status: 'uploading', progress: 0 });

            const formData = new FormData();
            formData.append('files[]', upload.file);
            if (currentFolderId) {
                formData.append('folder_id', currentFolderId);
            }

            const response = await axios.post(route('admin.vault.upload'), formData, {
                onUploadProgress: (progressEvent) => {
                    const percent = Math.round((progressEvent.loaded * 100) / (progressEvent.total || 100));
                    updateUploadState(upload.id, { progress: percent });
                }
            });

            // Upload complete -> 'Processing'
            updateUploadState(upload.id, { status: 'processing', progress: 100 });

            // Simulate processing delay
            await new Promise(resolve => setTimeout(resolve, 800));

            const { uploaded, errors } = response.data;
            const hasError = errors && errors.some((e: any) => e.filename === upload.file.name);

            if (hasError) {
                const errorData = errors.find((e: any) => e.filename === upload.file.name);
                const errorMsg = errorData.error;

                // Determine failed stage
                let failedStage: PipelineStage = 'ValidateMimeType';

                if (errorMsg.includes('Double extension')) failedStage = 'DetectDoubleExtension';
                else if (errorMsg.includes('MIME')) failedStage = 'ValidateMimeType';
                else if (errorMsg.includes('Virus') || errorMsg.includes('malware')) failedStage = 'SandboxedScan';
                else if (errorMsg.includes('uuid')) failedStage = 'GenerateUuid';
                else if (errorMsg.includes('metadata')) failedStage = 'StoreMetadata';

                // Wait for animation
                await new Promise(resolve => setTimeout(resolve, 1500));

                updateUploadState(upload.id, {
                    status: 'error',
                    errorStage: failedStage,
                    errorMessage: errorMsg
                });
            } else {
                // Success - wait for animation
                await new Promise(resolve => setTimeout(resolve, 3000));
                updateUploadState(upload.id, { status: 'completed' });
                onUploadComplete();
            }

        } catch (error: any) {
            let msg = 'Upload failed';
            if (error.response?.status === 413) msg = 'File too large';
            else if (error.response?.status === 422) msg = error.response.data.message;

            updateUploadState(upload.id, {
                status: 'error',
                errorMessage: msg,
                errorStage: 'ValidateMimeType'
            });
        } finally {
            setIsUploading(false);
        }
    }, [currentFolderId, onUploadComplete, updateUploadState]);

    // Effect to handle hashing idle files
    useEffect(() => {
        const isHashing = uploads.some(u => u.status === 'hashing' || u.status === 'checking');
        if (isHashing) return;

        const nextToHash = uploads.find(u => u.status === 'idle');
        if (nextToHash) {
            // Skip client-side hashing for files larger than 10MB to protect client memory
            if (nextToHash.file.size > 10 * 1024 * 1024) {
                updateUploadState(nextToHash.id, { status: 'ready' });
            } else {
                runHashAndCheck(nextToHash);
            }
        }
    }, [uploads, runHashAndCheck, updateUploadState]);

    // Effect to process upload queue
    useEffect(() => {
        const nextUpload = uploads.find(u => u.status === 'ready');
        if (nextUpload && !isUploading) {
            processUpload(nextUpload);
        }
    }, [uploads, isUploading, processUpload]);

    const handleForceUpload = (id: string) => {
        setUploads(prev => prev.map(u => u.id === id ? { ...u, status: 'ready', forceUpload: true } : u));
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-2xl max-h-[85vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle>Upload Files</DialogTitle>
                    <DialogDescription>
                        Drag and drop files here or click to browse. Max size {maxUploadSize}MB.
                    </DialogDescription>
                </DialogHeader>

                <div className="flex-1 overflow-y-auto pr-2 space-y-4 min-h-[300px]">
                    {/* Drop Zone */}
                    <div
                        {...getRootProps()}
                        className={`
                            border-2 border-dashed rounded-xl p-8 flex flex-col items-center justify-center text-center cursor-pointer transition-colors
                            ${isDragActive ? 'border-primary bg-primary/5' : 'border-muted hover:border-primary/50 hover:bg-muted/50'}
                        `}
                    >
                        <input {...getInputProps()} />
                        <div className="bg-muted rounded-full p-4 mb-4">
                            <Upload className="h-8 w-8 text-muted-foreground" />
                        </div>
                        <p className="text-sm font-medium">Click to upload or drag and drop</p>
                        <p className="text-xs text-muted-foreground mt-1">
                            SVG, PNG, JPG or GIF (max {maxUploadSize}MB)
                        </p>
                    </div>

                    {/* File List */}
                    <div className="space-y-3">
                        {uploads.map(upload => (
                            <div key={upload.id} className="relative group">
                                <UploadPipelineTracker
                                    file={upload.file}
                                    uploadProgress={upload.progress}
                                    status={upload.status}
                                    errorStage={upload.errorStage}
                                    errorMessage={upload.errorMessage === 'Duplicate detected' ? null : upload.errorMessage}
                                />

                                {upload.errorMessage === 'Duplicate detected' && (upload as any).duplicateInfo && (
                                    <div className="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-900 shadow-sm animate-in fade-in slide-in-from-top-2">
                                        <div className="flex items-start gap-3">
                                            <div className="bg-amber-100 p-2 rounded-full mt-0.5">
                                                <AlertTriangle className="h-4 w-4 text-amber-600" />
                                            </div>
                                            <div className="flex-1">
                                                <p className="font-semibold">Duplicate Detected</p>
                                                <p className="text-amber-800/80 mt-1">
                                                    This file exactly matches <strong>{(upload as any).duplicateInfo.original_name}</strong>, 
                                                    uploaded on {new Date((upload as any).duplicateInfo.created_at).toLocaleDateString()} 
                                                    {(upload as any).duplicateInfo.folder ? ` in ${(upload as any).duplicateInfo.folder.name}` : ''}.
                                                </p>
                                                <div className="mt-3 flex gap-2">
                                                    <Button size="sm" variant="outline" className="h-8 border-amber-200 hover:bg-amber-100 hover:text-amber-900" onClick={() => removeUpload(upload.id)}>
                                                        Skip
                                                    </Button>
                                                    <Button size="sm" className="h-8 bg-amber-600 hover:bg-amber-700 text-white border-0" onClick={() => handleForceUpload(upload.id)}>
                                                        Upload Anyway
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {upload.status !== 'uploading' && upload.status !== 'processing' && (
                                    <button
                                        onClick={() => removeUpload(upload.id)}
                                        className="absolute top-2 right-2 text-muted-foreground hover:text-destructive opacity-0 group-hover:opacity-100 transition-opacity"
                                    >
                                        <X className="h-4 w-4" />
                                    </button>
                                )}
                            </div>
                        ))}
                    </div>
                </div>

                <DialogFooter className="mt-4 border-t pt-4">
                    <div className="flex items-center justify-between w-full">
                        <Button variant="ghost" onClick={clearCompleted} disabled={!uploads.some(u => u.status === 'completed')}>
                            Clear Completed
                        </Button>
                        <Button onClick={() => onOpenChange(false)} variant="outline">
                            Close
                        </Button>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

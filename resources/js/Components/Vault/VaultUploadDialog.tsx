import { useState, useCallback, useEffect } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Upload, X } from 'lucide-react';
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
    id: string; // unique ID for key
    file: File;
    progress: number;
    status: 'idle' | 'uploading' | 'processing' | 'completed' | 'error';
    errorStage?: PipelineStage | null;
    errorMessage?: string | null;
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

    const processUpload = useCallback(async (upload: FileUploadState) => {
        setIsUploading(true);

        // Update status to uploading
        updateUploadState(upload.id, { status: 'uploading', progress: 0 });

        const formData = new FormData();
        formData.append('files[]', upload.file);
        if (currentFolderId) {
            formData.append('folder_id', currentFolderId);
        }

        try {
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
            const isSuccess = uploaded && uploaded.some((u: any) => u.original_name === upload.file.name);

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

    // Effect to process queue
    useEffect(() => {
        const nextUpload = uploads.find(u => u.status === 'idle');
        if (nextUpload && !isUploading) {
            processUpload(nextUpload);
        }
    }, [uploads, isUploading, processUpload]);

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
                                    errorMessage={upload.errorMessage}
                                />
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

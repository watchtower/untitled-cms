import { useEffect, useState } from 'react';
import { Check, X, Loader2, Circle } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Progress } from '@/Components/ui/progress';

export type PipelineStage =
    | 'ValidateMimeType'
    | 'DetectDoubleExtension'
    | 'SandboxedScan'
    | 'SanitizeImage'
    | 'GenerateUuid'
    | 'StoreMetadata';

export const ALL_STAGES: PipelineStage[] = [
    'ValidateMimeType',
    'DetectDoubleExtension',
    'SandboxedScan',
    'SanitizeImage',
    'GenerateUuid',
    'StoreMetadata'
];

interface UploadPipelineTrackerProps {
    file: File;
    uploadProgress: number; // 0-100
    status: 'idle' | 'uploading' | 'processing' | 'completed' | 'error';
    errorStage?: PipelineStage | null;
    errorMessage?: string | null;
}

export default function UploadPipelineTracker({
    file,
    uploadProgress,
    status,
    errorStage,
    errorMessage
}: UploadPipelineTrackerProps) {
    const [currentStageIndex, setCurrentStageIndex] = useState(-1);

    // Simulate pipeline progress when status is 'processing' or 'completed'
    useEffect(() => {
        if (status === 'uploading') {
            setCurrentStageIndex(-1);
        } else if (status === 'processing') {
            // Start simulation
            let stage = 0;
            const interval = setInterval(() => {
                stage++;
                setCurrentStageIndex(prev => {
                    const next = prev + 1;
                    if (next >= ALL_STAGES.length) {
                        clearInterval(interval);
                        return ALL_STAGES.length - 1;
                    }
                    // Stop if we hit the error stage
                    if (errorStage && ALL_STAGES[next] === errorStage) {
                        clearInterval(interval);
                        return next;
                    }
                    return next;
                });
            }, 600); // 600ms per stage for visualization

            return () => clearInterval(interval);
        } else if (status === 'completed') {
            setCurrentStageIndex(ALL_STAGES.length - 1);
        } else if (status === 'error' && errorStage) {
            const idx = ALL_STAGES.indexOf(errorStage);
            if (idx !== -1) setCurrentStageIndex(idx);
        }
    }, [status, errorStage]);

    return (
        <div className="space-y-3 p-3 border rounded-lg bg-card shadow-sm">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3 overflow-hidden">
                    <div className="h-8 w-8 rounded bg-muted flex items-center justify-center shrink-0">
                        {file.type.startsWith('image/') ? (
                            <img
                                src={URL.createObjectURL(file)}
                                alt="preview"
                                className="h-full w-full object-cover rounded"
                                onLoad={(e) => URL.revokeObjectURL(e.currentTarget.src)}
                            />
                        ) : (
                            <span className="text-xs font-bold text-muted-foreground">FILE</span>
                        )}
                    </div>
                    <div className="min-w-0">
                        <p className="text-sm font-medium truncate">{file.name}</p>
                        <p className="text-xs text-muted-foreground">{(file.size / 1024).toFixed(1)} KB</p>
                    </div>
                </div>

                <div className="text-xs font-medium">
                    {status === 'idle' && 'Waiting...'}
                    {status === 'uploading' && `${uploadProgress}% Uploading`}
                    {status === 'processing' && 'Processing...'}
                    {status === 'completed' && <span className="text-green-600">Completed</span>}
                    {status === 'error' && <span className="text-destructive">Failed</span>}
                </div>
            </div>

            {/* Progress Bar for Upload */}
            {(status === 'uploading' || (status === 'processing' && currentStageIndex === -1)) && (
                <Progress value={uploadProgress} className="h-1.5" />
            )}

            {/* Pipeline Visualization */}
            {(status === 'processing' || status === 'completed' || status === 'error') && (
                <div className="relative pt-2">
                    <div className="flex justify-between items-center relative z-10">
                        {ALL_STAGES.map((stage, index) => {
                            const isPast = index < currentStageIndex;
                            const isCurrent = index === currentStageIndex;
                            const isError = status === 'error' && isCurrent;
                            const isCompleted = status === 'completed' || (isPast && !isError);

                            return (
                                <div key={stage} className="flex flex-col items-center gap-1 group">
                                    <div className={cn(
                                        "w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all duration-300 bg-background",
                                        isCompleted && "border-green-500 bg-green-50 text-green-600",
                                        isCurrent && !isError && "border-blue-500 animate-pulse text-blue-500",
                                        isError && "border-destructive bg-destructive/10 text-destructive",
                                        !isPast && !isCurrent && "border-muted text-muted-foreground"
                                    )}>
                                        {isCompleted && <Check className="w-3 h-3" />}
                                        {isCurrent && !isError && <Loader2 className="w-3 h-3 animate-spin" />}
                                        {isError && <X className="w-3 h-3" />}
                                        {!isPast && !isCurrent && <Circle className="w-2 h-2 fill-muted-foreground/20" />}
                                    </div>
                                    <span className={cn(
                                        "text-[10px] font-medium max-w-[60px] text-center leading-tight transition-colors",
                                        isCompleted ? "text-green-600" : "text-muted-foreground",
                                        isError && "text-destructive font-bold"
                                    )}>
                                        {stage.replace(/([A-Z])/g, ' $1').trim()}
                                    </span>
                                </div>
                            );
                        })}
                    </div>
                    {/* Connecting Line */}
                    <div className="absolute top-[19px] left-3 right-3 h-0.5 bg-muted -z-0">
                        <div
                            className={cn("h-full bg-green-500 transition-all duration-500 ease-out", status === 'error' ? 'bg-destructive' : '')}
                            style={{ width: `${(Math.max(0, currentStageIndex) / (ALL_STAGES.length - 1)) * 100}%` }}
                        />
                    </div>
                </div>
            )}

            {/* Error Message */}
            {status === 'error' && errorMessage && (
                <div className="text-xs text-destructive bg-destructive/5 p-2 rounded flex items-center gap-2 mt-2">
                    <X className="h-4 w-4 shrink-0" />
                    <span>{errorMessage}</span>
                </div>
            )}
        </div>
    );
}

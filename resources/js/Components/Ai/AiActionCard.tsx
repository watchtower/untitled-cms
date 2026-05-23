import { CheckCircle, XCircle, Loader2, AlertTriangle, ChevronDown, ChevronUp } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { useState } from 'react';

interface ActionProposal {
    action: string;
    description: string;
    resolved_title?: string;
    needs_restore?: boolean;
    params: Record<string, any>;
}

interface AiActionCardProps {
    proposal: ActionProposal;
    onConfirm: () => void;
    onCancel: () => void;
    isExecuting: boolean;
}

const LONG_THRESHOLD = 80;

const stripHtml = (html: string) =>
    html.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

export default function AiActionCard({ proposal, onConfirm, onCancel, isExecuting }: AiActionCardProps) {
    const [showFull, setShowFull] = useState(false);

    const entries = Object.entries(proposal.params).filter(
        ([, v]) => v !== null && v !== undefined && String(v).trim() !== ''
    );

    const hasLongValues = entries.some(([, v]) => String(v).length > LONG_THRESHOLD);

    return (
        <div className="rounded-xl border border-amber-500/30 bg-amber-50/50 dark:bg-amber-950/20 p-3 space-y-2.5 text-sm w-full">
            {/* Header */}
            <div className="flex items-center gap-2 font-semibold text-amber-700 dark:text-amber-400 text-xs uppercase tracking-wide">
                <AlertTriangle className="h-3.5 w-3.5 shrink-0" />
                Confirm Action
            </div>

            {/* Description */}
            <p className="text-foreground font-medium text-sm leading-snug">{proposal.description}</p>

            {proposal.resolved_title && (
                <p className="text-xs text-muted-foreground">
                    Record: <span className="font-medium text-foreground">"{proposal.resolved_title}"</span>
                </p>
            )}

            {proposal.needs_restore && (
                <div className="bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 p-2 rounded text-xs flex items-start gap-1.5 border border-amber-200 dark:border-amber-800/50">
                    <AlertTriangle className="h-3.5 w-3.5 shrink-0 mt-0.5" />
                    <p>This record was previously deleted (Undo/Trash). It will be <strong>restored</strong> before applying these changes.</p>
                </div>
            )}

            {/* Params preview */}
            {entries.length > 0 && (
                <div className="bg-background/70 border border-border/50 rounded-lg p-2.5 space-y-1.5">
                    {entries.map(([key, value]) => {
                        const raw = String(value);
                        // Strip HTML for content fields so we don't show raw tags
                        const isContent = key === 'content';
                        const str = isContent ? stripHtml(raw) : raw;
                        const isLong = str.length > LONG_THRESHOLD;
                        const display = isLong && !showFull ? str.slice(0, LONG_THRESHOLD) + '…' : str;

                        return (
                            <div key={key} className="flex gap-2 text-xs">
                                <span className="text-muted-foreground w-20 shrink-0 capitalize">
                                    {key.replace(/_/g, ' ')}:
                                </span>
                                <span className="text-foreground wrap-break-word min-w-0">
                                    {display}
                                </span>
                            </div>
                        );
                    })}

                    {hasLongValues && (
                        <button
                            onClick={() => setShowFull(v => !v)}
                            className="flex items-center gap-1 text-[10px] text-muted-foreground hover:text-foreground mt-1"
                        >
                            {showFull
                                ? <><ChevronUp className="h-3 w-3" /> Show less</>
                                : <><ChevronDown className="h-3 w-3" /> Show full content</>
                            }
                        </button>
                    )}
                </div>
            )}

            {/* Actions */}
            <div className="flex gap-2 pt-0.5">
                <Button
                    size="sm"
                    variant="outline"
                    onClick={onCancel}
                    disabled={isExecuting}
                    className="flex-1 h-8 text-xs"
                >
                    <XCircle className="h-3 w-3 mr-1.5" />
                    Cancel
                </Button>
                <Button
                    size="sm"
                    onClick={onConfirm}
                    disabled={isExecuting}
                    className="flex-1 h-8 text-xs bg-amber-600 hover:bg-amber-700 text-white border-0"
                >
                    {isExecuting
                        ? <Loader2 className="h-3 w-3 mr-1.5 animate-spin" />
                        : <CheckCircle className="h-3 w-3 mr-1.5" />
                    }
                    Confirm
                </Button>
            </div>
        </div>
    );
}

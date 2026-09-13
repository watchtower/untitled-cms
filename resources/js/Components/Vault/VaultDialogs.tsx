import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { VaultFolder } from '@/types/vault';
import { cn } from '@/lib/utils';
import { Download, Folder, Loader2, Move, Sparkles, Wand2 } from 'lucide-react';

type Props = {
    isCreateFolderOpen: boolean;
    setIsCreateFolderOpen: (v: boolean) => void;
    newFolderName: string;
    setNewFolderName: (v: string) => void;
    handleCreateFolder: () => void;
    isMoveOpen: boolean;
    setIsMoveOpen: (v: boolean) => void;
    folders: VaultFolder[];
    selectedCount: number;
    selectedFolderIds: (string | null | undefined)[];
    moveTarget: string | null;
    setMoveTarget: (v: string | null) => void;
    handleMove: () => void;
    isImageGenOpen: boolean;
    setIsImageGenOpen: (v: boolean) => void;
    imageGenPrompt: string;
    setImageGenPrompt: (v: string) => void;
    isGeneratingImage: boolean;
    isSavingImage: boolean;
    generatedImageUrl: string | null;
    setGeneratedImageUrl: (v: string | null) => void;
    handleGenerateImage: () => void;
};

export default function VaultDialogs({
    isCreateFolderOpen,
    setIsCreateFolderOpen,
    newFolderName,
    setNewFolderName,
    handleCreateFolder,
    isMoveOpen,
    setIsMoveOpen,
    folders,
    selectedCount,
    selectedFolderIds,
    moveTarget,
    setMoveTarget,
    handleMove,
    isImageGenOpen,
    setIsImageGenOpen,
    imageGenPrompt,
    setImageGenPrompt,
    isGeneratingImage,
    isSavingImage,
    generatedImageUrl,
    setGeneratedImageUrl,
    handleGenerateImage,
}: Props) {
    return (
        <>
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
                        <Button variant="ghost" onClick={() => setIsCreateFolderOpen(false)}>
                            Cancel
                        </Button>
                        <Button onClick={handleCreateFolder}>Create</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={isMoveOpen} onOpenChange={setIsMoveOpen}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Move className="h-5 w-5 text-primary" />
                            Move {selectedCount} Item{selectedCount !== 1 ? 's' : ''} to Folder
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-2 max-h-72 overflow-y-auto py-2">
                        <button
                            onClick={() => setMoveTarget(null)}
                            className={cn(
                                'w-full flex items-center gap-2 px-3 py-2 rounded-md text-sm text-left transition-colors',
                                moveTarget === null
                                    ? 'bg-primary text-primary-foreground'
                                    : 'hover:bg-muted',
                            )}
                        >
                            <Folder className="h-4 w-4 shrink-0" />
                            <span className="font-medium">Root</span>
                        </button>
                        {folders.map((folder) => (
                            <button
                                key={folder.id}
                                onClick={() => setMoveTarget(folder.id)}
                                disabled={selectedFolderIds.some((id) => id === folder.id)}
                                className={cn(
                                    'w-full flex items-center gap-2 px-3 py-2 rounded-md text-sm text-left transition-colors disabled:opacity-40 disabled:cursor-not-allowed',
                                    moveTarget === folder.id
                                        ? 'bg-primary text-primary-foreground'
                                        : 'hover:bg-muted',
                                )}
                            >
                                <Folder className="h-4 w-4 shrink-0" />
                                {folder.name}
                            </button>
                        ))}
                        {folders.length === 0 && (
                            <p className="text-sm text-muted-foreground text-center py-4">
                                No folders exist yet.
                            </p>
                        )}
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setIsMoveOpen(false)}>
                            Cancel
                        </Button>
                        <Button onClick={handleMove}>
                            <Move className="mr-2 h-4 w-4" /> Move Here
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

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
                            <Label htmlFor="image-gen-prompt">
                                Describe the image you want to create
                            </Label>
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
                                Uses your <strong>active AI Hub</strong> to generate an image from
                                your description. Supported providers: <strong>OpenAI</strong>{' '}
                                (DALL-E 3), <strong>Gemini</strong> (Imagen 3), and{' '}
                                <strong>Stability AI</strong> (SDXL).
                            </p>
                        </div>

                        {generatedImageUrl && (
                            <div className="space-y-3">
                                <div className="rounded-lg overflow-hidden border aspect-square max-h-80 flex items-center justify-center bg-muted/20">
                                    <img
                                        src={generatedImageUrl}
                                        alt="AI Generated"
                                        className="object-contain w-full h-full"
                                    />
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
                                        onClick={() => {
                                            setGeneratedImageUrl(null);
                                            setImageGenPrompt('');
                                        }}
                                    >
                                        Generate Another
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setIsImageGenOpen(false)}>
                            Close
                        </Button>
                        <Button
                            onClick={handleGenerateImage}
                            disabled={isGeneratingImage || isSavingImage || !imageGenPrompt.trim()}
                        >
                            {isGeneratingImage ? (
                                <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Generating...
                                </>
                            ) : isSavingImage ? (
                                <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Saving to Vault...
                                </>
                            ) : (
                                <>
                                    <Sparkles className="mr-2 h-4 w-4" />
                                    Generate &amp; Save to Vault
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

import { Dialog, DialogPortal, DialogOverlay } from '@/Components/ui/dialog';
import { Dialog as DialogPrimitive } from 'radix-ui';
import { PropsWithChildren } from 'react';

export default function Modal({
    children,
    show = false,
    maxWidth = '2xl',
    closeable = true,
    onClose = () => { },
}: PropsWithChildren<{
    show: boolean;
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
    closeable?: boolean;
    onClose: CallableFunction;
}>) {
    const close = () => {
        if (closeable) {
            onClose();
        }
    };

    const maxWidthClass = {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[maxWidth];

    return (
        <Dialog open={show} onOpenChange={(isOpen) => !isOpen && close()}>
            <DialogPortal>
                <DialogOverlay className="bg-black/80 backdrop-blur-none" />
                <div className="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto px-4 py-6 sm:px-0">
                    <DialogPrimitive.Content
                        className={`mb-6 transform overflow-hidden rounded-lg bg-background text-foreground shadow-xl sm:mx-auto sm:w-full data-open:animate-in data-closed:animate-out data-closed:fade-out-0 data-open:fade-in-0 data-closed:zoom-out-95 data-open:zoom-in-95 data-closed:slide-out-to-left-1/2 data-closed:slide-out-to-top-[48%] data-open:slide-in-from-left-1/2 data-open:slide-in-from-top-[48%] ${maxWidthClass}`}
                    >
                        {children}
                    </DialogPrimitive.Content>
                </div>
            </DialogPortal>
        </Dialog>
    );
}

import { useState } from 'react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

interface ImagePickerProps {
    value: string | string[];
    onChange: (url: string | string[]) => void;
    multiple?: boolean;
}

export default function ImagePicker({ value, onChange, multiple = false }: ImagePickerProps) {
    const [isOpen, setIsOpen] = useState(false);

    const openFileManager = () => {
        window.dispatchEvent(new CustomEvent('open-vault-picker', {
            detail: {
                mode: multiple ? 'multiple' : 'single',
                type: 'image',
                onSelect: (files: any[]) => {
                    if (multiple) {
                        onChange(files.map(f => f.url));
                    } else {
                        onChange(files[0]?.url || '');
                    }
                }
            }
        }));
    };

    const currentValue = multiple && typeof value === 'string' ? [value] : value;
    const displayValue = Array.isArray(currentValue)
        ? currentValue.filter(Boolean).join(', ')
        : currentValue;

    return (
        <div className="flex gap-2">
            <Input
                type="text"
                value={displayValue || ''}
                readOnly
                placeholder={multiple ? "Select images..." : "Select image..."}
                className="flex-1 bg-background"
                onClick={openFileManager}
            />
            <Button
                type="button"
                variant="outline"
                onClick={openFileManager}
            >
                Browse
            </Button>
            {displayValue && (
                <Button
                    type="button"
                    variant="ghost"
                    onClick={() => onChange(multiple ? [] : '')}
                >
                    Clear
                </Button>
            )}
        </div>
    );
}

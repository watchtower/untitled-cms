import { Input } from "@/Components/ui/input";
import { AiInput } from "@/Components/Ai/AiInput";
import { Label } from "@/Components/ui/label";
import { AiTextarea } from "@/Components/Ai/AiTextarea";
import { Switch } from "@/Components/ui/switch";
import ImagePicker from "@/Components/ImagePicker";
import { useEffect, useRef } from "react";

interface SettingInputProps {
    setting: {
        key: string;
        value: any;
        type: string;
        label: string;
        description?: string;
    };
    onChange: (value: any) => void;
    onSave: (value: any) => void;
}

export default function SettingInput({ setting, onChange, onSave }: SettingInputProps) {
    // specific ref to hold the debounce timer
    const debounceTimer = useRef<NodeJS.Timeout | null>(null);

    const handleChange = (val: any) => {
        // Immediate UI update
        onChange(val);

        // Auto-save logic
        const immediateSaveTypes = ['boolean', 'image'];

        if (immediateSaveTypes.includes(setting.type)) {
            // Cancel any pending debounce
            if (debounceTimer.current) clearTimeout(debounceTimer.current);
            onSave(val);
        } else {
            // Debounce save for text inputs
            if (debounceTimer.current) clearTimeout(debounceTimer.current);
            debounceTimer.current = setTimeout(() => {
                onSave(val);
            }, 800);
        }
    };

    // Cleanup on unmount
    useEffect(() => {
        return () => {
            if (debounceTimer.current) clearTimeout(debounceTimer.current);
        };
    }, []);

    // Custom layout for boolean settings (Switch with description)
    if (setting.type === 'boolean') {
        return (
            <div className="flex flex-row items-center justify-between rounded-lg border p-4 shadow-sm">
                <div className="space-y-0.5">
                    <Label htmlFor={setting.key} className="text-base font-medium text-foreground">
                        {setting.label}
                    </Label>
                    {setting.description && (
                        <p className="text-[0.8rem] text-muted-foreground">
                            {setting.description}
                        </p>
                    )}
                </div>
                <Switch
                    id={setting.key}
                    checked={!!setting.value}
                    onCheckedChange={(checked) => handleChange(checked)}
                />
            </div>
        );
    }

    // Standard layout for other inputs
    return (
        <div className="grid gap-2">
            <Label htmlFor={setting.key} className="text-base font-medium">
                {setting.label}
            </Label>

            {setting.description && (
                <p className="text-sm text-muted-foreground">{setting.description}</p>
            )}

            {setting.type === 'text' && (
                <AiInput
                    id={setting.key}
                    value={setting.value || ''}
                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => handleChange(e.target.value)}
                    onGeneration={(text) => handleChange(text)}
                    className="max-w-xl"
                    aiPromptLabel={`Generate ${setting.label}`}
                    aiPromptPlaceholder={`e.g. Write a realistic value for ${setting.label}`}
                />
            )}

            {setting.type === 'number' && (
                <Input
                    id={setting.key}
                    type="number"
                    value={setting.value || ''}
                    onChange={(e) => handleChange(e.target.value)}
                    className="max-w-xl"
                />
            )}

            {setting.type === 'textarea' && (
                <AiTextarea
                    id={setting.key}
                    value={setting.value || ''}
                    onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => handleChange(e.target.value)}
                    onGeneration={(text) => handleChange(text)}
                    className="min-h-[100px] max-w-xl"
                    aiPromptLabel={`Generate ${setting.label}`}
                    aiPromptPlaceholder={`e.g. Write a comprehensive text for ${setting.label}`}
                />
            )}

            {setting.type === 'image' && (
                <div className="max-w-xl">
                    <ImagePicker
                        value={setting.value}
                        onChange={(url) => handleChange(url)}
                    />
                    {setting.value && (
                        <div className="mt-2">
                            <img
                                src={setting.value}
                                alt="Preview"
                                className="h-32 w-auto object-contain rounded-md border"
                            />
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

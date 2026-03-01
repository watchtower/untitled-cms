import { useState } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, router } from "@inertiajs/react";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { Button } from "@/Components/ui/button";
import SettingInput from "@/Components/Settings/SettingInput";
import { toast } from "sonner";

interface Setting {
    key: string;
    value: any;
    group: string;
    type: string;
    label: string;
    description?: string;
}

interface Props {
    settings: Record<string, Setting[]>;
}

export default function SettingsIndex({ settings }: Props) {
    const [localSettings, setLocalSettings] = useState(settings);
    const groups = Object.keys(settings);

    const formatGroupLabel = (group: string) => {
        const labels: Record<string, string> = {
            ai: 'AI Features',
        };
        return labels[group] ?? (group.charAt(0).toUpperCase() + group.slice(1));
    };

    // Helper to update local state immediately for UI responsiveness
    const updateLocalSetting = (group: string, key: string, value: any) => {
        setLocalSettings((prev) => ({
            ...prev,
            [group]: prev[group].map((s) =>
                s.key === key ? { ...s, value } : s
            ),
        }));
    };

    const handleSave = (key: string, value: any) => {
        router.put(route('settings.update', key), { value }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                // specific toast for non-text inputs or just silent success?
                // User asked for "no prompt", implying unobtrusive. 
                // We'll show a small toast for confirmation but not block anything.
                // toast.dismiss(); // Dismiss previous to avoid stacking
                toast.success("Saved", { duration: 1500, position: 'bottom-right' });
            },
            onError: () => {
                toast.error("Failed to save setting");
            }
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Settings
                </h2>
            }
        >
            <Head title="Settings" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <Tabs defaultValue={groups[0]} className="w-full">
                        <TabsList className="mb-4">
                            {groups.map((group) => (
                                <TabsTrigger key={group} value={group}>
                                    {formatGroupLabel(group)}
                                </TabsTrigger>
                            ))}
                        </TabsList>

                        {groups.map((group) => (
                            <TabsContent key={group} value={group}>
                                <Card>
                                    <CardHeader>
                                        <CardTitle>{formatGroupLabel(group)} Settings</CardTitle>
                                        <CardDescription>
                                            Manage your {formatGroupLabel(group)} configurations. Changes are saved automatically.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        {group === 'integrations' ? (
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                {localSettings[group].map((setting) => (
                                                    <div
                                                        key={setting.key}
                                                        className={
                                                            ['text', 'textarea', 'image'].includes(setting.type)
                                                                ? 'col-span-1 md:col-span-2'
                                                                : 'col-span-1'
                                                        }
                                                    >
                                                        <SettingInput
                                                            setting={setting}
                                                            onChange={(val) => updateLocalSetting(group, setting.key, val)}
                                                            onSave={(val) => handleSave(setting.key, val)}
                                                        />
                                                    </div>
                                                ))}
                                            </div>
                                        ) : (
                                            // Default listing for other groups
                                            localSettings[group].map((setting) => (
                                                <div key={setting.key} className="flex items-end gap-4 border-b pb-6 last:border-0 last:pb-0">
                                                    <div className="flex-1">
                                                        <SettingInput
                                                            setting={setting}
                                                            onChange={(val) => updateLocalSetting(group, setting.key, val)}
                                                            onSave={(val) => handleSave(setting.key, val)}
                                                        />
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </CardContent>
                                </Card>
                            </TabsContent>
                        ))}
                    </Tabs>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

import { CKEditor } from '@ckeditor/ckeditor5-react';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { useEffect, useRef, useState } from 'react';
import axios from 'axios';
import { toast } from 'sonner';

interface EditorProps {
    value: string;
    onChange: (data: string) => void;
    height?: number;
}

declare global {
    interface Window {
        tinymce: any;
        SetUrl: any;
    }
}

export default function Editor({ value, onChange, height = 500 }: EditorProps) {
    const { tinymce_api_key } = usePage<PageProps>().props;
    const editorRef = useRef<any>(null);
    const [isLoaded, setIsLoaded] = useState(false);
    const id = 'tinymce-editor-' + Math.random().toString(36).substr(2, 9);

    // Check for dark mode
    const isDarkMode = typeof window !== 'undefined' && window.document.documentElement.classList.contains('dark');



    // Store latest value/onChange to avoid stale closures in callbacks
    const valueRef = useRef(value);
    const onChangeRef = useRef(onChange);

    useEffect(() => {
        valueRef.current = value;
        onChangeRef.current = onChange;

        if (editorRef.current && window.tinymce) {
            const editor = window.tinymce.get(id);
            if (editor && editor.getContent() !== value) {
                if (!editor.hasFocus()) {
                    editor.setContent(value);
                }
            }
        }
    }, [value, onChange, id]);


    useEffect(() => {
        // Load TinyMCE Script
        if (!window.tinymce) {
            const script = document.createElement('script');
            // Use API key if available (Standard Cloud), otherwise use custom CDN
            if (tinymce_api_key) {
                script.src = `https://cdn.tiny.cloud/1/${tinymce_api_key}/tinymce/7/tinymce.min.js`;
            } else {
                script.src = 'https://tinymce.fastdns.my/js/tinymce/tinymce.min.js';
            }
            script.referrerPolicy = 'origin';
            script.onload = () => {
                setIsLoaded(true);
            };
            document.head.appendChild(script);
        } else {
            setIsLoaded(true);
        }
    }, [tinymce_api_key]);


    useEffect(() => {
        if (isLoaded && window.tinymce) {
            window.tinymce.init({
                selector: `#${id}`,
                height: height,
                menubar: true,
                promotion: false, // Hide "Explore trial"
                relative_urls: false,
                remove_script_host: true,
                convert_urls: true,
                // Skin selection based on dark mode
                skin: isDarkMode ? 'oxide-dark' : 'oxide',
                content_css: isDarkMode ? 'dark' : 'default',
                width: '100%',

                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'help', 'wordcount'
                ],
                contextmenu: 'link image table | ai_menu',
                toolbar: 'undo redo image | blocks | ' +
                    'bold italic | alignleft aligncenter alignright | ' +
                    'bullist numlist outdent indent | removeformat fullscreen',
                content_style: isDarkMode
                    ? 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px; background-color: #09090b; color: #fff; }'
                    : 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',



                setup: (editor: any) => {
                    editorRef.current = editor;

                    editor.on('init', () => {
                        if (valueRef.current) {
                            editor.setContent(valueRef.current);
                        }
                    });

                    editor.on('Change', () => {
                        const content = editor.getContent();
                        onChangeRef.current(content);
                    });

                    // Add Custom AI Context Menu Items
                    editor.ui.registry.addMenuItem('ai_rewrite', {
                        text: '🪄 AI: Rewrite Selection',
                        icon: 'highlight-bg-color',
                        onAction: async () => {
                            const selectedText = editor.selection.getContent({ format: 'text' });
                            if (!selectedText) {
                                toast.error('Please select some text to rewrite first.');
                                return;
                            }

                            toast.loading('AI is rewriting...', { id: 'ai-gen' });
                            try {
                                const response = await axios.post('/ai/generate', {
                                    prompt: `Rewrite the following text to be more professional, engaging, and concise. Do NOT add any extra conversational filler, just return the rewritten text directly:\n\n"${selectedText}"`
                                });
                                if (response.data?.generated_text) {
                                    editor.execCommand('mceInsertContent', false, response.data.generated_text);
                                    toast.success('Text rewritten successfully', { id: 'ai-gen' });
                                }
                            } catch (e: any) {
                                toast.error('AI Generation failed. Check API key.', { id: 'ai-gen' });
                            }
                        }
                    });

                    editor.ui.registry.addMenuItem('ai_expand', {
                        text: '➕ AI: Expand Selection',
                        icon: 'plus',
                        onAction: async () => {
                            const selectedText = editor.selection.getContent({ format: 'text' });
                            if (!selectedText) {
                                toast.error('Please select some text to expand first.');
                                return;
                            }

                            toast.loading('AI is expanding...', { id: 'ai-gen' });
                            try {
                                const response = await axios.post('/ai/generate', {
                                    prompt: `Expand on the following text by adding 2-3 more sentences of relevant, detailed context. Do NOT add any extra conversational filler, just return the expanded text directly:\n\n"${selectedText}"`
                                });
                                if (response.data?.generated_text) {
                                    editor.execCommand('mceInsertContent', false, response.data.generated_text);
                                    toast.success('Text expanded successfully', { id: 'ai-gen' });
                                }
                            } catch (e: any) {
                                toast.error('AI Generation failed. Check API key.', { id: 'ai-gen' });
                            }
                        }
                    });

                    editor.ui.registry.addContextMenu('ai_menu', {
                        update: (element: any) => {
                            // Only show if text is selected
                            return editor.selection.isCollapsed() ? '' : 'ai_rewrite ai_expand';
                        }
                    });
                },
                file_picker_callback: (callback: any, value: any, meta: any) => {
                    const typeMap: Record<string, string> = {
                        image: 'image',
                        file: 'all',
                        media: 'all' // Video/Audio support if needed
                    };

                    window.dispatchEvent(new CustomEvent('open-vault-picker', {
                        detail: {
                            mode: 'single',
                            type: typeMap[meta.filetype] || 'all',
                            onSelect: (files: any[]) => {
                                if (files.length > 0) {
                                    const file = files[0];
                                    callback(file.url, { alt: file.alt_text || file.original_name });
                                }
                            }
                        }
                    }));
                }
            });
        }

        return () => {
            if (window.tinymce) {
                window.tinymce.remove(`#${id}`);
            }
        };
    }, [isLoaded, id]);

    // Handle dynamic height changes
    useEffect(() => {
        if (isLoaded && window.tinymce && editorRef.current) {
            const editor = window.tinymce.get(id);
            if (editor && editor.theme && editor.theme.resizeTo) {
                editor.theme.resizeTo(null, height);
            }
        }
    }, [height, isLoaded, id]);

    return (
        <div className="min-h-[400px]">
            <style>{`
                .tox-promotion { display: none !important; }
            `}</style>
            <textarea id={id} style={{ visibility: 'hidden' }} defaultValue={value}></textarea>
        </div>
    );
}

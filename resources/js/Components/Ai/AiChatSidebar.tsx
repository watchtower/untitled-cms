import { useState, useRef, useEffect, useCallback } from 'react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/Components/ui/sheet';
import { ScrollArea } from '@/Components/ui/scroll-area';
import { Send, Sparkles, User, Bot, Loader2, Eraser, History, Plus, Trash2, CheckCircle, ExternalLink, AlertTriangle } from 'lucide-react';
import axios from 'axios';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';
import { usePage } from '@inertiajs/react';
import ReactMarkdown from 'react-markdown';
import AiActionCard from './AiActionCard';

interface Message {
    role: 'user' | 'assistant' | 'system';
    content: string;
    proposal?: ActionProposal | null;
    actionResult?: ActionResult | null;
}

interface ActionProposal {
    action: string;
    description: string;
    resolved_title?: string;
    resolved_id?: string;
    params: Record<string, any>;
}

interface ActionResult {
    subject: string;
    id: string;
    title: string;
    url?: string;
}

interface ChatSessionMeta {
    id: string;
    title: string;
    last_active_at: string;
}

const QUICK_PROMPTS = [
    'Help me write a blog intro',
    'Suggest SEO tags for this page',
    'How many pages are published?',
    'What can you help with?',
];

export default function AiChatSidebar() {
    const { aiChatEnabled, url } = usePage().props as any;
    if (!aiChatEnabled) return null;

    const [isOpen, setIsOpen] = useState(false);
    const [showHistory, setShowHistory] = useState(false);
    const [input, setInput] = useState('');
    const [sessionId, setSessionId] = useState<string | null>(null);
    const [sessions, setSessions] = useState<ChatSessionMeta[]>([]);
    const [messages, setMessages] = useState<Message[]>([
        { role: 'assistant', content: "Hello! I'm your AI Assistant. How can I help you today?" }
    ]);
    const [isLoading, setIsLoading] = useState(false);
    const [pendingProposal, setPendingProposal] = useState<{ proposal: ActionProposal; msgIndex: number } | null>(null);
    const [isExecuting, setIsExecuting] = useState(false);
    const scrollRef = useRef<HTMLDivElement>(null);

    // Auto-scroll
    useEffect(() => {
        if (scrollRef.current) {
            const c = scrollRef.current.querySelector('[data-radix-scroll-area-viewport]');
            if (c) c.scrollTop = c.scrollHeight;
        }
    }, [messages]);

    // Load sessions when history panel opens or sidebar opens
    const loadSessions = useCallback(async () => {
        try {
            const { data } = await axios.get(route('admin.ai.sessions.index'));
            setSessions(data);
        } catch { }
    }, []);

    useEffect(() => {
        if (isOpen) loadSessions();
    }, [isOpen]);

    useEffect(() => {
        if (showHistory) loadSessions();
    }, [showHistory]);

    // Persist messages after each exchange
    const persistMessages = useCallback(async (id: string, msgs: Message[]) => {
        const persistable = msgs
            .filter(m => m.role !== 'system')
            .map(m => ({ role: m.role, content: m.content }));
        try {
            const { data } = await axios.put(route('admin.ai.sessions.update', id), { messages: persistable });
            // Update title in session list if auto-generated
            if (data.title) {
                setSessions(prev => prev.map(s => s.id === id ? { ...s, title: data.title } : s));
            }
        } catch { }
    }, []);

    const startNewSession = useCallback(async () => {
        try {
            const { data } = await axios.post(route('admin.ai.sessions.store'));
            setSessionId(data.id);
        } catch { }
    }, []);

    const loadSession = useCallback(async (id: string) => {
        try {
            const { data } = await axios.get(route('admin.ai.sessions.show', id));
            setSessionId(id);
            setMessages(data.messages?.length
                ? data.messages
                : [{ role: 'assistant', content: "Continuing our conversation. How can I help?" }]
            );
            setShowHistory(false);
            setPendingProposal(null);
        } catch {
            toast.error('Could not load session.');
        }
    }, []);

    const deleteSession = useCallback(async (id: string) => {
        try {
            await axios.delete(route('admin.ai.sessions.destroy', id));
            setSessions(prev => prev.filter(s => s.id !== id));
            if (sessionId === id) {
                setSessionId(null);
                clearChat();
            }
        } catch {
            toast.error('Could not delete session.');
        }
    }, [sessionId]);

    const handleSend = async (overrideInput?: string) => {
        const text = overrideInput ?? input;
        if (!text.trim() || isLoading) return;

        // Ensure a session exists
        let currentSessionId = sessionId;
        if (!currentSessionId) {
            try {
                const { data } = await axios.post(route('admin.ai.sessions.store'));
                currentSessionId = data.id;
                setSessionId(data.id);
            } catch { }
        }

        const contextMessage: Message = {
            role: 'system',
            content: `The admin is currently on the CMS page: ${url ?? 'unknown'}.`,
        };

        const userMessage: Message = { role: 'user', content: text };
        const history = messages.filter(m => m.role !== 'system');
        const apiMessages: Message[] = [contextMessage, ...history, userMessage];

        const displayMessages = [...history, userMessage];
        setMessages(displayMessages);
        setInput('');
        setIsLoading(true);

        try {
            const { data } = await axios.post(route('admin.ai.chat'), { messages: apiMessages, page_url: url ?? window.location.pathname });
            const rawContent: string = data.message;

            // Detect [ACTION]...[/ACTION] block — use [\ s\S] for multiline JSON
            const actionMatch = rawContent.match(/\[ACTION\]([\s\S]*?)\[\/ACTION\]/);
            let proposal: ActionProposal | null = null;
            let displayContent = rawContent.replace(/\[ACTION\][\s\S]*?\[\/ACTION\]/, '').trim();

            if (actionMatch) {
                let resolveError: string | null = null;
                try {
                    const actionJson = JSON.parse(actionMatch[1].trim());
                    const { data: resolveData } = await axios.post(route('admin.ai.actions.resolve'), {
                        action_json: actionJson,
                    });
                    proposal = resolveData.proposal ?? null;
                    resolveError = resolveData.error ?? null;
                    // If AI gave no conversational text, add a generic intro
                    if (!displayContent && proposal) {
                        displayContent = `I'll ${proposal.action.replace(/_/g, ' ')} for you. Please review the details below:`;
                    }
                    // Surface resolve error so user knows why action failed
                    if (!proposal && resolveError && !displayContent) {
                        displayContent = `⚠️ I tried to perform that action but ran into an issue: **${resolveError}**\n\nPlease check the record name and try again.`;
                    }
                } catch (err: any) {
                    proposal = null;
                    const errMsg = err?.response?.data?.error;
                    if (!displayContent) {
                        displayContent = errMsg
                            ? `⚠️ Action failed: **${errMsg}**`
                            : 'I wasn\'t able to perform that action. Please try again.';
                    }
                }
            }

            const assistantMsg: Message = {
                role: 'assistant',
                // NEVER use rawContent — it contains the [ACTION] block.
                // displayContent is already stripped. Fall back to a generic msg if empty.
                content: displayContent || (proposal ? '' : 'I wasn\'t able to process that request. Please try again.'),
                proposal,
            };

            const finalMessages = [...displayMessages, assistantMsg];
            setMessages(finalMessages);

            if (proposal) {
                setPendingProposal({ proposal, msgIndex: finalMessages.length - 1 });
            }

            // Persist
            if (currentSessionId) {
                await persistMessages(currentSessionId, finalMessages);
            }

        } catch (error: any) {
            const serverMsg = error.response?.data?.error || error.response?.data?.message;
            const status = error.response?.status;
            const msg = serverMsg
                ? `AI Chat error (${status}): ${serverMsg}`
                : (error.message || 'AI Chat failed. Please check your AI Hub configuration.');
            toast.error(msg);
        } finally {
            setIsLoading(false);
        }
    };

    const handleConfirmAction = async () => {
        if (!pendingProposal) return;
        setIsExecuting(true);

        try {
            const { data } = await axios.post(route('admin.ai.actions.execute'), { proposal: pendingProposal.proposal });
            const result: ActionResult = data.result;

            // Replace the message's proposal with the result
            setMessages(prev => prev.map((m, i) =>
                i === pendingProposal.msgIndex
                    ? { ...m, proposal: null, actionResult: result }
                    : m
            ));

            setPendingProposal(null);
            toast.success(`${result.subject} "${result.title}" updated successfully.`);

        } catch (error: any) {
            toast.error(error.response?.data?.error || 'Action failed.');
        } finally {
            setIsExecuting(false);
        }
    };

    const handleCancelAction = () => {
        if (!pendingProposal) return;
        setMessages(prev => prev.map((m, i) =>
            i === pendingProposal.msgIndex ? { ...m, proposal: null } : m
        ));
        setPendingProposal(null);
    };

    const clearChat = () => {
        setMessages([{ role: 'assistant', content: 'Chat cleared. How can I help you now?' }]);
        setSessionId(null);
        setPendingProposal(null);
    };

    return (
        <Sheet open={isOpen} onOpenChange={setIsOpen}>
            <SheetTrigger asChild>
                <Button
                    variant="outline"
                    size="icon"
                    className="fixed bottom-6 right-6 h-12 w-12 rounded-full shadow-lg border-primary/20 bg-background/80 backdrop-blur-xs z-50 group hover:scale-110 transition-transform"
                    title="AI Assistant"
                >
                    <Sparkles className="h-6 w-6 text-primary group-hover:animate-pulse" />
                </Button>
            </SheetTrigger>
            <SheetContent side="right" className="w-[420px] sm:w-[520px] flex flex-col p-0 gap-0">

                {/* Header */}
                <SheetHeader className="p-4 border-b shrink-0">
                    <div className="flex items-center justify-between">
                        <SheetTitle className="flex items-center gap-2 text-base">
                            <Sparkles className="h-4 w-4 text-primary" />
                            AI Assistant
                        </SheetTitle>
                        <div className="flex items-center gap-1">
                            <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => { clearChat(); startNewSession(); }} title="New conversation">
                                <Plus className="h-4 w-4 text-muted-foreground" />
                            </Button>
                            <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => setShowHistory(v => !v)} title="Chat history">
                                <History className="h-4 w-4 text-muted-foreground" />
                            </Button>
                            <Button variant="ghost" size="icon" className="h-8 w-8" onClick={clearChat} title="Clear conversation">
                                <Eraser className="h-4 w-4 text-muted-foreground" />
                            </Button>
                        </div>
                    </div>
                </SheetHeader>

                {/* History panel */}
                {showHistory && (
                    <div className="border-b shrink-0 bg-muted/30">
                        <p className="text-xs font-semibold text-muted-foreground px-4 pt-3 pb-1">Recent Conversations</p>
                        {sessions.length === 0 ? (
                            <p className="text-xs text-muted-foreground px-4 pb-3">No saved conversations yet.</p>
                        ) : (
                            <div className="max-h-48 overflow-y-auto divide-y divide-border">
                                {sessions.map(s => (
                                    <div key={s.id} className="flex items-center gap-2 px-4 py-2 hover:bg-muted/60 transition-colors group">
                                        <button
                                            className="flex-1 text-left text-sm truncate"
                                            onClick={() => loadSession(s.id)}
                                        >
                                            {s.title}
                                        </button>
                                        <button
                                            onClick={() => deleteSession(s.id)}
                                            className="opacity-0 group-hover:opacity-100 transition-opacity text-destructive/60 hover:text-destructive"
                                        >
                                            <Trash2 className="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}

                {/* Messages */}
                <ScrollArea className="flex-1 p-4" ref={scrollRef}>
                    <div className="space-y-4 pb-4">
                        {messages.map((msg, i) => (
                            <div key={i}>
                                <div className={cn(
                                    'flex gap-3 text-sm',
                                    msg.role === 'user' ? 'flex-row-reverse' : 'flex-row'
                                )}>
                                    <div className={cn(
                                        'h-8 w-8 rounded-full flex items-center justify-center shrink-0 shadow-xs',
                                        msg.role === 'user' ? 'bg-primary text-primary-foreground' : 'bg-muted'
                                    )}>
                                        {msg.role === 'user' ? <User className="h-4 w-4" /> : <Bot className="h-4 w-4 text-primary" />}
                                    </div>
                                    {msg.content && (
                                        <div className={cn(
                                            'rounded-2xl px-4 py-2 max-w-[85%] leading-relaxed',
                                            msg.role === 'user'
                                                ? 'bg-primary text-primary-foreground rounded-tr-none'
                                                : 'bg-muted rounded-tl-none'
                                        )}>
                                            {msg.role === 'user' ? (
                                                msg.content
                                            ) : (
                                                <div className="prose prose-sm dark:prose-invert max-w-none prose-p:my-1 prose-ul:my-1 prose-li:my-0 prose-headings:my-1">
                                                    <ReactMarkdown>{msg.content}</ReactMarkdown>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>

                                {/* Action pending indicator (replaces inline card) */}
                                {msg.proposal && pendingProposal?.msgIndex === i && (
                                    <div className="mt-2 ml-11">
                                        <span className="inline-flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-full px-2.5 py-1">
                                            <AlertTriangle className="h-3 w-3" />
                                            Confirmation required below ↓
                                        </span>
                                    </div>
                                )}

                                {/* Action result */}
                                {msg.actionResult && (
                                    <div className="mt-3 ml-11 flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                                        <CheckCircle className="h-4 w-4 shrink-0" />
                                        <span>{msg.actionResult.subject} "{msg.actionResult.title}" — done!</span>
                                        {msg.actionResult.url && (
                                            <a href={msg.actionResult.url} className="underline flex items-center gap-1" target="_blank" rel="noreferrer">
                                                View <ExternalLink className="h-3 w-3" />
                                            </a>
                                        )}
                                    </div>
                                )}
                            </div>
                        ))}

                        {isLoading && (
                            <div className="flex gap-3 text-sm animate-in fade-in slide-in-from-bottom-2">
                                <div className="h-8 w-8 rounded-full bg-muted flex items-center justify-center shrink-0">
                                    <Bot className="h-4 w-4 text-primary" />
                                </div>
                                <div className="bg-muted rounded-2xl rounded-tl-none px-4 py-2 flex items-center gap-2 text-muted-foreground italic">
                                    <Loader2 className="h-3 w-3 animate-spin" />
                                    AI is thinking...
                                </div>
                            </div>
                        )}
                    </div>
                </ScrollArea>

                {/* Quick prompts */}
                {messages.length === 1 && !pendingProposal && (
                    <div className="px-4 pb-2 flex flex-wrap gap-2 shrink-0">
                        {QUICK_PROMPTS.map(p => (
                            <button
                                key={p}
                                onClick={() => handleSend(p)}
                                className="text-xs bg-muted hover:bg-muted/70 text-muted-foreground border border-border rounded-full px-3 py-1 transition-colors hover:text-foreground"
                            >
                                {p}
                            </button>
                        ))}
                    </div>
                )}

                {/* Sticky confirmation card — above input, never overlaps messages */}
                {pendingProposal && (
                    <div className="px-4 pb-3 shrink-0 border-t bg-background animate-in slide-in-from-bottom-2">
                        <div className="pt-3">
                            <AiActionCard
                                proposal={pendingProposal.proposal}
                                onConfirm={handleConfirmAction}
                                onCancel={handleCancelAction}
                                isExecuting={isExecuting}
                            />
                        </div>
                    </div>
                )}

                {/* Input */}
                <div className="p-4 border-t bg-background shrink-0">
                    <div className="flex gap-2">
                        <Input
                            placeholder="Ask me anything, or ask me to do something..."
                            value={input}
                            onChange={e => setInput(e.target.value)}
                            onKeyDown={e => e.key === 'Enter' && handleSend()}
                            className="bg-muted/50 border-transparent focus-visible:ring-primary"
                            disabled={!!pendingProposal}
                        />
                        <Button size="icon" onClick={() => handleSend()} disabled={!input.trim() || isLoading || !!pendingProposal}>
                            {isLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
                        </Button>
                    </div>
                    {pendingProposal && (
                        <p className="text-[10px] text-center text-amber-600 mt-2">Please confirm or cancel the action above before continuing.</p>
                    )}
                    {!pendingProposal && (
                        <p className="text-[10px] text-center text-muted-foreground mt-2">
                            Powered by your active AI Hub. History is saved per session.
                        </p>
                    )}
                </div>
            </SheetContent>
        </Sheet>
    );
}

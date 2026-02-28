import { useState, useRef, useEffect } from 'react';
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
import { MessageSquare, Send, Sparkles, User, Bot, Loader2, Eraser } from 'lucide-react';
import axios from 'axios';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';

interface Message {
    role: 'user' | 'assistant' | 'system';
    content: string;
}

export default function AiChatSidebar() {
    const [isOpen, setIsOpen] = useState(false);
    const [input, setInput] = useState('');
    const [messages, setMessages] = useState<Message[]>([
        { role: 'assistant', content: "Hello! I'm your AI Assistant. How can I help you with your content today?" }
    ]);
    const [isLoading, setIsLoading] = useState(false);
    const scrollRef = useRef<HTMLDivElement>(null);

    // Auto-scroll to bottom on new messages
    useEffect(() => {
        if (scrollRef.current) {
            const scrollContainer = scrollRef.current.querySelector('[data-radix-scroll-area-viewport]');
            if (scrollContainer) {
                scrollContainer.scrollTop = scrollContainer.scrollHeight;
            }
        }
    }, [messages]);

    const handleSend = async () => {
        if (!input.trim() || isLoading) return;

        const userMessage: Message = { role: 'user', content: input };
        const newMessages = [...messages, userMessage];

        setMessages(newMessages);
        setInput('');
        setIsLoading(true);

        try {
            const response = await axios.post('/ai/chat', { messages: newMessages });
            setMessages([...newMessages, { role: 'assistant', content: response.data.message }]);
        } catch (error: any) {
            toast.error(error.response?.data?.error || 'AI Chat failed. Please check your AI Hub configuration.');
            console.error(error);
        } finally {
            setIsLoading(false);
        }
    };

    const clearChat = () => {
        setMessages([{ role: 'assistant', content: "Chat cleared. How can I help you now?" }]);
    };

    return (
        <Sheet open={isOpen} onOpenChange={setIsOpen}>
            <SheetTrigger asChild>
                <Button
                    variant="outline"
                    size="icon"
                    className="fixed bottom-6 right-6 h-12 w-12 rounded-full shadow-lg border-primary/20 bg-background/80 backdrop-blur-sm z-50 group hover:scale-110 transition-transform"
                >
                    <Sparkles className="h-6 w-6 text-primary group-hover:animate-pulse" />
                </Button>
            </SheetTrigger>
            <SheetContent side="right" className="w-[400px] sm:w-[500px] flex flex-col p-0 gap-0">
                <SheetHeader className="p-6 border-b shrink-0">
                    <div className="flex items-center justify-between">
                        <SheetTitle className="flex items-center gap-2">
                            <Sparkles className="h-5 w-5 text-primary" />
                            AI Assistant
                        </SheetTitle>
                        <Button variant="ghost" size="icon" onClick={clearChat} title="Clear conversation">
                            <Eraser className="h-4 w-4 text-muted-foreground" />
                        </Button>
                    </div>
                </SheetHeader>

                <ScrollArea className="flex-1 p-6" ref={scrollRef}>
                    <div className="space-y-4 pb-4">
                        {messages.map((msg, i) => (
                            <div
                                key={i}
                                className={cn(
                                    "flex gap-3 text-sm",
                                    msg.role === 'user' ? "flex-row-reverse" : "flex-row"
                                )}
                            >
                                <div className={cn(
                                    "h-8 w-8 rounded-full flex items-center justify-center shrink-0 shadow-sm",
                                    msg.role === 'user' ? "bg-primary text-primary-foreground" : "bg-muted"
                                )}>
                                    {msg.role === 'user' ? <User className="h-4 w-4" /> : <Bot className="h-4 w-4 text-primary" />}
                                </div>
                                <div className={cn(
                                    "rounded-2xl px-4 py-2 max-w-[85%] leading-relaxed",
                                    msg.role === 'user'
                                        ? "bg-primary text-primary-foreground rounded-tr-none"
                                        : "bg-muted rounded-tl-none"
                                )}>
                                    {msg.content}
                                </div>
                            </div>
                        ))}
                        {isLoading && (
                            <div className="flex gap-3 text-sm animate-in fade-in slide-in-from-bottom-2">
                                <div className="h-8 w-8 rounded-full bg-muted flex items-center justify-center shrink-0">
                                    <Bot className="h-4 w-4 text-primary" />
                                </div>
                                <div className="bg-muted rounded-2xl rounded-tl-none px-4 py-2 flex items-center gap-2 text-muted-foreground italic">
                                    <Loader2 className="h-3 w-3 animate-spin" />
                                    AI is typing...
                                </div>
                            </div>
                        )}
                    </div>
                </ScrollArea>

                <div className="p-6 border-t bg-background shrink-0">
                    <div className="flex gap-2">
                        <Input
                            placeholder="Ask me anything..."
                            value={input}
                            onChange={(e) => setInput(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleSend()}
                            className="bg-muted/50 border-transparent focus-visible:ring-primary"
                        />
                        <Button size="icon" onClick={handleSend} disabled={!input.trim() || isLoading}>
                            {isLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
                        </Button>
                    </div>
                    <p className="text-[10px] text-center text-muted-foreground mt-3">
                        Powered by your active AI Hub. Conversations are not persisted between sessions.
                    </p>
                </div>
            </SheetContent>
        </Sheet>
    );
}

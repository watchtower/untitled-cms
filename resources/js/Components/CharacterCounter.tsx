import { cn } from '@/lib/utils';

interface CharacterCounterProps {
    current: number;
    ideal: { min: number; max: number };
    max?: number;
    className?: string;
}

export function CharacterCounter({ current, ideal, max, className }: CharacterCounterProps) {
    const getColor = () => {
        if (current === 0) return 'text-muted-foreground';
        if (current < ideal.min) return 'text-yellow-600 dark:text-yellow-500';
        if (current >= ideal.min && current <= ideal.max) return 'text-green-600 dark:text-green-500';
        if (max && current > max) return 'text-destructive';
        return 'text-yellow-600 dark:text-yellow-500';
    };

    const getMessage = () => {
        if (current === 0) return '';
        if (current < ideal.min) return 'Too short';
        if (current >= ideal.min && current <= ideal.max) return 'Optimal';
        if (max && current > max) return 'Too long';
        return 'A bit long';
    };

    return (
        <div className={cn('flex items-center justify-between text-xs', className)}>
            <span className={getColor()}>
                {getMessage()}
            </span>
            <span className={getColor()}>
                {current} / {ideal.max}
                {max && current > ideal.max && ` (max: ${max})`}
            </span>
        </div>
    );
}

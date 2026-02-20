import { Moon, Sun } from "lucide-react"
import { Button } from "@/Components/ui/button"
import { useTheme } from "@/Components/theme-provider"

export function ModeToggle() {
    const { theme, setTheme } = useTheme()

    const cycleTheme = () => {
        setTheme(theme === 'light' ? 'dark' : 'light')
    }

    return (
        <Button variant="outline" size="icon" onClick={cycleTheme} title={`Toggle theme (current: ${theme})`}>
            <Sun className={`h-[1.2rem] w-[1.2rem] transition-all rotate-0 scale-100 dark:-rotate-90 dark:scale-0`} />
            <Moon className="absolute h-[1.2rem] w-[1.2rem] rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
            <span className="sr-only">Toggle theme</span>
        </Button>
    )
}

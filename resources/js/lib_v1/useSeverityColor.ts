// composables/useSeverityColor.ts
export function useSeverityColor() {
    const getSeverityColor = (severity: string): string => {
        const tokenMap: Record<string, string> = {
            primary: '--p-tag-primary-color',
            success: '--p-tag-success-color',
            info: '--p-tag-info-color',
            warn: '--p-tag-warn-color',
            danger: '--p-tag-danger-color',
            error: '--p-tag-danger-color',
            secondary: '--p-tag-secondary-color',
        }

        const token = tokenMap[severity]
        if (!token) return 'currentColor'

        return getComputedStyle(document.documentElement)
            .getPropertyValue(token)
            .trim()
    }

    const lightenColor = (color: string, amount = 0.15): string => {
        // Handle oklch (PrimeVue v4)
        const oklchMatch = color.match(/oklch\(([\d.]+)\s+([\d.]+)\s+([\d.]+)\)/)
        if (oklchMatch) {
            const l = Math.min(1, parseFloat(oklchMatch[1]) + amount)
            return `oklch(${l} ${oklchMatch[2]} ${oklchMatch[3]})`
        }

        // Handle hex
        if (color.startsWith('#')) {
            const hex = color.replace('#', '')
            const r = Math.min(255, parseInt(hex.substring(0, 2), 16) + Math.round(amount * 255))
            const g = Math.min(255, parseInt(hex.substring(2, 4), 16) + Math.round(amount * 255))
            const b = Math.min(255, parseInt(hex.substring(4, 6), 16) + Math.round(amount * 255))
            return `rgb(${r}, ${g}, ${b})`
        }

        // Handle rgb(...)
        const rgbMatch = color.match(/rgb\(([\d.]+),\s*([\d.]+),\s*([\d.]+)\)/)
        if (rgbMatch) {
            const r = Math.min(255, parseFloat(rgbMatch[1]) + Math.round(amount * 255))
            const g = Math.min(255, parseFloat(rgbMatch[2]) + Math.round(amount * 255))
            const b = Math.min(255, parseFloat(rgbMatch[3]) + Math.round(amount * 255))
            return `rgb(${r}, ${g}, ${b})`
        }

        return color
    }

    const getSeverityColorLight = (severity: string, amount = 0.15): string => {
        return lightenColor(getSeverityColor(severity), amount)
    }

    return { getSeverityColor, lightenColor, getSeverityColorLight }
}

import { SeverityOption } from '@/types';

enum PrimeSeverityEnum {
    Primary = 'primary',
    Secondary = 'secondary',
    Success = 'success',
    Info = 'info',
    Warn = 'warn',
    Danger = 'danger',
    Contrast = 'contrast',
}

export const severityOptions: SeverityOption[] = [
    { label: 'Primary', value: PrimeSeverityEnum.Primary },
    { label: 'Secondary', value: PrimeSeverityEnum.Secondary },
    { label: 'Success', value: PrimeSeverityEnum.Success },
    { label: 'Info', value: PrimeSeverityEnum.Info },
    { label: 'Warn', value: PrimeSeverityEnum.Warn },
    { label: 'Danger', value: PrimeSeverityEnum.Danger },
    { label: 'Contrast', value: PrimeSeverityEnum.Contrast },
];

export const getSeverityLabel = (severity:PrimeSeverityEnum | undefined | null): string => {
    const value = severity ?? ''; // ubah undefined → ''
    const found = severityOptions.find((option) => option.value === value);
    return found?.label ?? 'Unknown';
};

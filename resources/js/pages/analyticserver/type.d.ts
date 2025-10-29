import { Hardware } from '@/types';

export type HardwareComponentCodeEnum = 'cpu' | 'gpu' | 'ram' | 'ssd' | 'mobo' | 'nic' | 'psu' | 'lc';

export type UnsuedHardware = {
    [key in HardwareComponentCodeEnum]?: Hardware[];
};

export interface HardwareReplacementForm {
    current_uuid: string | null;
    status_uuid: string | null;
    replacement_uuid: string | null;
}

export interface MaintenanceForm {
    _method: 'PUT';
    replacements: Record<HardwareComponentCodeEnum, HardwareReplacementForm>;
    [key: string]: any;
}

import type { PrimeSeverity } from '@/types';

export interface MasterDataItem {
    id: string;
    name: string;
    severity: PrimeSeverity;
    created_at?: string;
}

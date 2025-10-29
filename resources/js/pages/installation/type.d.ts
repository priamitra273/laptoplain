import { Site, Pagination } from '@/types'

export interface Regency {
    uuid: string;
    name: string;
}

export interface Installation extends Site {
    cctv?: PreconfigCamera[]
}

export interface Attachment {
    name: string;
    url: string;
}

export interface SiteHistory {
    status: string;
    remark: string;
    attachments?: Attachment[];
    created_at: string;
    created_by: string;
}

interface ListPagination extends Pagination {
    data: Installation[],
}

interface LogPagination extends Pagination {
    data: SiteHistory[]
}

interface FilterParams {
    regency_uuid?: {
        matchMode?: string;
        value?: string | string[] | null
    },
    [key: string]: any,
}

interface DatatableParams {
    rows: number;
    page?: number | null;
    sortField?: string | ((item: any) => string) | null;
    sortOrder?: 1 | 0 | -1 | null;
    filters?: FilterParams;
    q?: string;
}

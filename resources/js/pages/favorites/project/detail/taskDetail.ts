import type { UploadedFile } from '@/types';
import type { TaskOption, TaskOptionUser } from './types';

export interface TaskDetail {
    title: string;
    description: string | null;
    parent_id: string | null;
    category: { id: string } | null;
    type_id: string | null;
    status_id: string | null;
    priority_id: string | null;
    start_date: string | null;
    due_date: string | null;
    is_archived: boolean;
    media: UploadedFile[];
    users: TaskOptionUser[];
    tags: TaskOption[];
}

const isRecord = (value: unknown): value is Record<string, unknown> => !!value && typeof value === 'object';

/** Array yang setiap itemnya objek dengan `key` bertipe string. Array kosong dianggap sah. */
const isArrayKeyedBy = (value: unknown, key: string): boolean =>
    Array.isArray(value) && value.every((item) => isRecord(item) && typeof item[key] === 'string');

/**
 * Semua field yang benar-benar dibaca `InputAttachment` saat merender lampiran, plus `uuid`
 * yang dipakai `syncMedia` di server untuk menentukan media mana yang dipertahankan.
 */
export const isMedia = (value: unknown): boolean =>
    isRecord(value) &&
    typeof value.uuid === 'string' &&
    value.uuid.trim() !== '' &&
    typeof value.file_name === 'string' &&
    typeof value.mime_type === 'string' &&
    typeof value.size === 'number' &&
    Number.isFinite(value.size) &&
    value.size >= 0 &&
    ((typeof value.url === 'string' && value.url.trim() !== '') ||
        (typeof value.original_url === 'string' && value.original_url.trim() !== ''));

/**
 * Payload detail wajib dipastikan utuh sebelum mengisi form drawer, dan pemeriksaannya tidak
 * boleh berhenti di `title`: endpoint update memperlakukan daftar kosong sebagai "hapus semua",
 * jadi `media`, `users`, dan `tags` yang hilang harus dibedakan dari yang memang kosong.
 *
 * Respons yang cacat ditolak seluruhnya, bukan disaring — daftar lampiran yang dikirim balik
 * dengan satu item terbuang akan membuat server menghapus file yang tidak ikut terkirim.
 */
export const isTaskDetail = (detail: unknown): detail is TaskDetail =>
    isRecord(detail) &&
    typeof detail.title === 'string' &&
    Array.isArray(detail.media) &&
    detail.media.every(isMedia) &&
    isArrayKeyedBy(detail.users, 'id') &&
    isArrayKeyedBy(detail.tags, 'id');

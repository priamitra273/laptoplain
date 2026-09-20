/**
 * Status yang tidak menuntut tanggal. Cerminan `requiresDates()` di `TaskStoreRequest` dan
 * daftar pengecualian di `TaskUpdateStatusRequest` — keduanya memakai nama status yang
 * dinormalkan, bukan id, karena id-nya berbeda antar-instalasi.
 */
const DATE_FREE_STATUSES = ['todo', 'blocked'];

const normalize = (statusName: string) => statusName.toLowerCase().replace(/\s+/g, '');

export const statusRequiresDueDate = (statusName: string): boolean => !DATE_FREE_STATUSES.includes(normalize(statusName));

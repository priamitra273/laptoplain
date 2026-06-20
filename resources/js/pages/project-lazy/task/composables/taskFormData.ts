const appendValue = (fd: FormData, key: string, value: unknown): void => {
    if (value === null || value === undefined) {
        return;
    }
    if (value instanceof File || value instanceof Blob) {
        fd.append(key, value);
        return;
    }
    if (Array.isArray(value)) {
        value.forEach((item, index) => appendValue(fd, `${key}[${index}]`, item));
        return;
    }
    if (typeof value === 'object') {
        Object.entries(value as Record<string, unknown>).forEach(([childKey, childValue]) => appendValue(fd, `${key}[${childKey}]`, childValue));
        return;
    }
    if (typeof value === 'boolean') {
        fd.append(key, value ? '1' : '0');
        return;
    }
    fd.append(key, String(value));
};

export const buildTaskFormData = (data: Record<string, unknown>): FormData => {
    const fd = new FormData();
    Object.entries(data).forEach(([key, value]) => appendValue(fd, key, value));
    return fd;
};

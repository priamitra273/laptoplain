import { describe, expect, it } from 'vitest';
import { buildTaskFormData } from './taskFormData';

const entries = (fd: FormData): Array<[string, FormDataEntryValue]> => [...fd.entries()];

describe('buildTaskFormData', () => {
    it('serializes scalars, booleans, arrays and nested objects in bracket notation', () => {
        const fd = buildTaskFormData({
            _method: 'PUT',
            title: 'A',
            is_archived: true,
            assign_users: ['x', 'y'],
            add_tag: { new: [{ name: 't', severity: 'info' }], exists: ['z'] },
            parent_id: null,
        });

        expect(entries(fd)).toEqual([
            ['_method', 'PUT'],
            ['title', 'A'],
            ['is_archived', '1'],
            ['assign_users[0]', 'x'],
            ['assign_users[1]', 'y'],
            ['add_tag[new][0][name]', 't'],
            ['add_tag[new][0][severity]', 'info'],
            ['add_tag[exists][0]', 'z'],
        ]);
    });

    it('appends File instances directly', () => {
        const file = new File(['data'], 'a.png', { type: 'image/png' });
        const fd = buildTaskFormData({ attachments: [file] });
        const value = fd.get('attachments[0]');
        expect(value).toBeInstanceOf(File);
        expect((value as File).name).toBe('a.png');
    });
});

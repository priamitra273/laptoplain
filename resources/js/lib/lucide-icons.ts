import * as icons from 'lucide-vue-next';

// nama ikon lucide-vue-next di-export dua kali (mis. "Home" dan "HomeIcon"); pakai yang tanpa akhiran "Icon"
export const lucideIconItems = Object.keys(icons)
    .filter((key) => !key.endsWith('Icon'))
    .map((name) => ({ label: name, value: name }));

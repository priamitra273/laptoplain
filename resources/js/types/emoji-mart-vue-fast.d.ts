declare module 'emoji-mart-vue-fast/src' {
    import type { Component } from 'vue';

    export const Emoji: Component;

    export class EmojiIndex {
        constructor(data: any);
        search(term: string): any[];
        emoji(id: string): any;
    }
}

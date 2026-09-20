import { ref } from 'vue';

/**
 * State machine drag-and-drop HTML5 untuk board berbasis kolom status.
 *
 * Hanya mengurus "kartu mana yang diseret" dan "kolom mana yang sedang disorot". Siapa yang
 * boleh menerima drop dan apa yang terjadi sesudahnya diserahkan ke pemanggil, karena aturan
 * itu berbeda antar-halaman: Kanban project membatasi lewat `allowedStatusIds`, My Task
 * menahan perpindahan sampai due date terisi.
 */
export function useBoardDragDrop<TStatus extends { id: string }>(
    canDropInto: (status: TStatus) => boolean,
    onDropped: (taskId: string, status: TStatus) => void,
) {
    const draggedTaskId = ref<string | null>(null);
    const dragOverStatusId = ref<string | null>(null);

    /** Kolom kosong berubah jadi ajakan drop selama ada kartu yang sedang diseret dan kolom ini menerimanya. */
    const isDropTarget = (status: TStatus) => !!draggedTaskId.value && canDropInto(status);

    const onDragStart = (event: DragEvent, taskId: string) => {
        draggedTaskId.value = taskId;

        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
        }
    };

    const onDragEnd = () => {
        draggedTaskId.value = null;
        dragOverStatusId.value = null;
    };

    /**
     * Dipakai untuk `dragenter` sekaligus `dragover`. Kolom baru sah jadi sasaran drop kalau
     * keduanya di-`preventDefault()`; dengan `dragover` saja, setiap kali kursor masuk ke elemen
     * anak (kartu, badge, ikon) browser sempat menampilkan kursor "dilarang" sampai `dragover`
     * berikutnya datang — itu sumber kedipannya.
     *
     * `preventDefault()` sengaja dipanggil di sini, bukan lewat modifier `.prevent` di template:
     * modifier jalan lebih dulu tanpa peduli izin, sehingga kolom terlarang pun ikut menerima.
     */
    const onDragOver = (event: DragEvent, status: TStatus) => {
        if (!draggedTaskId.value || !canDropInto(status)) {
            return;
        }

        event.preventDefault();

        if (event.dataTransfer) {
            event.dataTransfer.dropEffect = 'move';
        }

        dragOverStatusId.value = status.id;
    };

    /**
     * `dragleave` ikut menggelembung dari elemen anak, jadi berpindah dari sisi kolom ke kartu di
     * dalamnya pun memicunya. Sorotan hanya dilepas kalau kursor benar-benar keluar dari kolomnya.
     */
    const onDragLeave = (event: DragEvent, status: TStatus) => {
        const column = event.currentTarget;
        const entering = event.relatedTarget;

        if (column instanceof Node && entering instanceof Node && column.contains(entering)) {
            return;
        }

        if (dragOverStatusId.value === status.id) {
            dragOverStatusId.value = null;
        }
    };

    const onDrop = (status: TStatus) => {
        const taskId = draggedTaskId.value;

        onDragEnd();

        if (!taskId || !canDropInto(status)) {
            return;
        }

        onDropped(taskId, status);
    };

    return { draggedTaskId, dragOverStatusId, isDropTarget, onDragStart, onDragEnd, onDragOver, onDragLeave, onDrop };
}

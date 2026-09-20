export function getPaginationRange(page: number, perPage: number, total: number) {
    return {
        start: total === 0 ? 0 : (page - 1) * perPage + 1,
        end: Math.min(page * perPage, total),
    };
}

import { CheckboxState } from "./type";

export function nodePropagateDown (node: any, check: boolean, selectionKeys: Record<string, CheckboxState>): void {
    if (check) selectionKeys[node.key] = { checked: true, partialChecked: false };
    else delete selectionKeys[node.key];

    if (node.children && node.children.length) {
        for (const child of node.children) {
            nodePropagateDown(child, check, selectionKeys);
        }
    }
}

export function nodePropagateUp (node: any, check: boolean, selectionKeys: Record<string, CheckboxState>, nodes: any[]): void {
    let checkedChildCount = 0;
    let childPartialSelected = false;

    if (node.data.parent_uuid) {
        const parentNode = findNode(nodes, node.data.parent_uuid)

        for (const child of parentNode.children) {
            if (selectionKeys[child.key] && selectionKeys[child.key].checked) checkedChildCount++;
            else if (selectionKeys[child.key] && selectionKeys[child.key].partialChecked) childPartialSelected = true;
        }

        if (check && checkedChildCount === parentNode.children.length) {
            selectionKeys[parentNode.key] = { checked: true, partialChecked: false };
        } else {
            if (!check) {
                delete selectionKeys[parentNode.key];
            }

            if (childPartialSelected || (checkedChildCount > 0 && checkedChildCount !== parentNode.children.length)) selectionKeys[parentNode.key] = { checked: false, partialChecked: true };
            else selectionKeys[parentNode.key] = { checked: false, partialChecked: false };
        }

        nodePropagateUp(parentNode, check, selectionKeys, nodes)
    }
}

export function findNode (nodes: any[], targetKey: string): any | null {
    for (const node of nodes) {
        if (node.key === targetKey) {
            return node;
        }
        if (node.children && node.children.length > 0) {
            const found = findNode(node.children, targetKey);
            if (found) return found;
        }
    }

    return null;
}
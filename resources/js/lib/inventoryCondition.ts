export function formatInventoryCondition(condition: string): string {
    return condition
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

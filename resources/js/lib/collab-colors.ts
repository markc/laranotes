const PALETTE = [
    { color: '#e06c75', light: '#e06c7533' },
    { color: '#98c379', light: '#98c37933' },
    { color: '#e5c07b', light: '#e5c07b33' },
    { color: '#61afef', light: '#61afef33' },
    { color: '#c678dd', light: '#c678dd33' },
    { color: '#56b6c2', light: '#56b6c233' },
    { color: '#d19a66', light: '#d19a6633' },
    { color: '#be5046', light: '#be504633' },
    { color: '#7ec699', light: '#7ec69933' },
    { color: '#f7ecb5', light: '#f7ecb533' },
    { color: '#cc99cd', light: '#cc99cd33' },
    { color: '#67cdcc', light: '#67cdcc33' },
] as const;

export function getCollabColor(userId: number) {
    return PALETTE[userId % PALETTE.length];
}

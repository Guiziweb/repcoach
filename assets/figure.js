
function eyes(cx, cy, mood, k) {
    const arcs = `<path d="M ${cx - 29 * k} ${cy + 2 * k} Q ${cx - 19 * k} ${cy - 14 * k} ${cx - 9 * k} ${cy + 2 * k} M ${cx + 9 * k} ${cy + 2 * k} Q ${cx + 19 * k} ${cy - 14 * k} ${cx + 29 * k} ${cy + 2 * k}" style="stroke: var(--eye)" stroke-width="${7 * k}"/>`;
    const ovals = (dx, dy) => `<ellipse cx="${cx - 19 * k + dx}" cy="${cy - 3 * k + dy}" rx="${7 * k}" ry="${11 * k}" style="fill: var(--eye)" stroke="none"/><ellipse cx="${cx + 19 * k + dx}" cy="${cy - 3 * k + dy}" rx="${7 * k}" ry="${11 * k}" style="fill: var(--eye)" stroke="none"/>`;

    return {
        ready: ovals(0, 0),
        think: ovals(6 * k, -13 * k),
        tired: `<path d="M ${cx - 28 * k} ${cy - 2 * k} A ${8 * k} ${8 * k} 0 0 0 ${cx - 12 * k} ${cy - 2 * k} Z M ${cx + 12 * k} ${cy - 2 * k} A ${8 * k} ${8 * k} 0 0 0 ${cx + 28 * k} ${cy - 2 * k} Z" style="fill: var(--eye)" stroke="none"/>`,
        flex: arcs,
        wave: arcs,
    }[mood];
}

function arms(pose, y, s, t) {
    const down = `<path d="M ${-28 * s} ${y} L ${-48 * s} ${y + 70 * s} L ${-36 * s} ${y + 130 * s}"/>`;

    return {
        ready: `${down}<path d="M ${28 * s} ${y} L ${48 * s} ${y + 70 * s} L ${36 * s} ${y + 130 * s}"/>`,
        think: `${down}<path d="M ${28 * s} ${y} L ${60 * s} ${y + 50 * s} L ${10 * s} ${y - 30 * s}"/>`,
        tired: `<path d="M ${-28 * s} ${y} L ${-44 * s} ${y + 72 * s} L ${-30 * s} ${y + 135 * s}"/><path d="M ${28 * s} ${y} L ${44 * s} ${y + 72 * s} L ${30 * s} ${y + 135 * s}"/>`,
        flex: `<path d="M ${-28 * s} ${y} L ${-95 * s} ${y - 5 * s} L ${-95 * s} ${y - 80 * s}"/><path d="M ${28 * s} ${y} L ${95 * s} ${y - 5 * s} L ${95 * s} ${y - 80 * s}"/><circle cx="${-95 * s}" cy="${y - 84 * s}" r="${15 * s}" fill="currentColor" stroke="none"/><circle cx="${95 * s}" cy="${y - 84 * s}" r="${15 * s}" fill="currentColor" stroke="none"/>`,
        wave: `${down}<path d="M ${28 * s} ${y} L ${80 * s} ${y - 40 * s} L ${110 * s + Math.sin(t * 8) * 25 * s} ${y - 110 * s}"/>`,
    }[pose];
}

function sweat(pose, y, s, t) {
    if ('tired' !== pose) {
        return '';
    }

    return [0, 1, 2].map((drop) => `<path d="M0,-12 C9,0 9,11 0,11 C-9,11 -9,0 0,-12 Z" style="fill: var(--sweat)" stroke="none" transform="translate(${70 * s + drop * 18 * s} ${y - 120 * s + ((t * 60 + drop * 25) % 70) * s})"/>`).join('');
}

export function figure(pose, t, s = 1) {
    const y = -260 * s + Math.sin(t * 2) * 4 * s;
    const hip = y + 100 * s;
    const spread = 'ready' === pose ? 40 : 26;
    const legs = `<path d="M 0 ${hip} L ${-spread * s} ${hip + 80 * s} L ${-(spread + 10) * s} 0"/><path d="M 0 ${hip} L ${spread * s} ${hip + 80 * s} L ${(spread + 10) * s} 0"/>`;

    return `<g stroke="currentColor" stroke-width="13" stroke-linecap="round" stroke-linejoin="round" fill="none">
        <ellipse cx="0" cy="6" rx="${120 * s}" ry="${14 * s}" fill="currentColor" opacity=".1" stroke="none"/>
        <line x1="${-28 * s}" y1="${y}" x2="${28 * s}" y2="${y}"/>${arms(pose, y, s, t)}
        <line x1="0" y1="${y}" x2="0" y2="${hip}"/>${legs}
        <line x1="0" y1="${y}" x2="0" y2="${y - 30 * s}" stroke-width="${22 * s}"/>
        <circle cx="0" cy="${y - 70 * s}" r="${46 * s}" fill="currentColor" stroke="none"/>${eyes(0, y - 70 * s, pose, s)}
        ${sweat(pose, y, s, t)}
    </g>`;
}

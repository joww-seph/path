// Keeps the map pieces, the directory list, and the name readout in sync.
const lifted = document.querySelector('[data-lifted]');

if (lifted) {
    const hint = document.querySelector('[data-readout-hint]');
    const name = document.querySelector('[data-readout-name]');
    const links = document.querySelectorAll('[data-barangay]');

    const setActive = (slug) => {
        links.forEach((link) =>
            link.classList.toggle('is-active', link.dataset.barangay === slug),
        );

        const piece = slug && document.getElementById(`piece-${slug}`);

        lifted.setAttribute('href', piece ? `#shape-${slug}` : '');
        hint.hidden = Boolean(piece);
        name.hidden = !piece;
        name.textContent = piece ? piece.dataset.name : '';
    };

    links.forEach((link) => {
        const slug = link.dataset.barangay;

        link.addEventListener('pointerenter', () => setActive(slug));
        link.addEventListener('focus', () => setActive(slug));
        link.addEventListener('pointerleave', () => setActive(null));
        link.addEventListener('blur', () => setActive(null));
    });
}

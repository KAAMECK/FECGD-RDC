// Le mouvement CSS est continu, sans flèches, sur les deux listes d’actualité.
document.querySelectorAll('.news-carousel').forEach(carousel => {
  const pauseButton = carousel.querySelector('[data-news-pause]');
  if (!pauseButton) return;
  let paused = false;
  const title = carousel.getAttribute('aria-label').replace(/^Photos : /, '');
  pauseButton.addEventListener('click', () => {
    paused = !paused;
    carousel.classList.toggle('news-is-paused', paused);
    pauseButton.textContent = paused ? 'Reprendre' : 'Pause';
    pauseButton.setAttribute('aria-pressed', String(paused));
    pauseButton.setAttribute('aria-label', `${paused ? 'Reprendre le défilement' : 'Mettre le défilement en pause'} : ${title}`);
  });
  document.addEventListener('visibilitychange', () => carousel.classList.toggle('news-is-hidden', document.hidden));
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(entries => carousel.classList.toggle('news-is-outside', !entries[0].isIntersecting)).observe(carousel);
  }
});

// Full-size viewing without leaving the article; Escape closes the photo.
const photoLinks = document.querySelectorAll('.article-body .gallery a, .article-body figure a, .article-body a.story-photo');
if (photoLinks.length && typeof HTMLDialogElement !== 'undefined') {
  const viewer = document.createElement('dialog');
  viewer.className = 'photo-viewer';
  viewer.setAttribute('aria-label', 'Photographie agrandie');
  const header = document.createElement('header');
  const label = document.createElement('span');
  const close = document.createElement('button');
  close.type = 'button'; close.textContent = 'Fermer ×';
  const photo = document.createElement('img');
  header.append(label, close); viewer.append(header, photo); document.body.append(viewer);
  close.addEventListener('click', () => viewer.close());
  viewer.addEventListener('click', event => { if (event.target === viewer) { const rect = viewer.getBoundingClientRect(); if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) viewer.close(); } });
  photoLinks.forEach(link => link.addEventListener('click', event => {
    if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    const original = link.querySelector('img'); if (!original) return;
    event.preventDefault(); photo.src = link.href; photo.alt = original.alt;
    label.textContent = original.alt || 'Photographie de la fédération'; viewer.showModal();
  }));
}

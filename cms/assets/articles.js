// Native media controls remain available with or without JavaScript.
// External video is loaded only after an explicit action; the direct link always works.
document.querySelectorAll('[data-youtube-id]').forEach(panel => {
  const id = panel.dataset.youtubeId;
  const button = panel.querySelector('[data-load-youtube]');
  if (!button || !/^[A-Za-z0-9_-]{11}$/.test(id || '')) return;
  button.hidden = false;
  button.addEventListener('click', () => {
    const frame = document.createElement('iframe');
    frame.src = `https://www.youtube-nocookie.com/embed/${id}?autoplay=1&rel=0`;
    frame.title = 'Journal écologique Green Impact TV — lecteur YouTube';
    frame.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
    frame.allowFullscreen = true;
    frame.referrerPolicy = 'strict-origin-when-cross-origin';
    panel.replaceChildren(frame);
    frame.focus();
    const state = panel.closest('.article-primary-video')?.querySelector('[data-video-state]');
    if (state) state.textContent = 'Lecteur YouTube chargé. Si la lecture est indisponible, utilisez le lien direct sous la vidéo.';
  }, { once: true });
});
document.querySelectorAll('[data-copy-article]').forEach(button => {
  if (!navigator.clipboard?.writeText || !window.isSecureContext) return;
  button.hidden = false;
  button.addEventListener('click', async () => {
    try {
      const link = new URL(location.href); link.hash = ''; link.search = '';
      await navigator.clipboard.writeText(link.href);
      button.textContent = 'Lien copié';
      document.querySelector('[data-share-status]').textContent = 'Le lien de cet article a été copié.';
    } catch {
      document.querySelector('[data-share-status]').textContent = 'Copiez le lien depuis la barre d’adresse de votre navigateur.';
    }
  });
});
document.querySelectorAll('.article-primary-video').forEach(panel => {
  const video = panel.querySelector('video');
  if (!video) return;
  const state = panel.querySelector('[data-video-state]');
  const set = text => { state.textContent = text; };
  video.addEventListener('loadstart', () => set('Chargement de la vidéo…'));
  video.addEventListener('waiting', () => set('Mise en mémoire tampon…'));
  video.addEventListener('playing', () => { set('Lecture en cours'); panel.classList.add('is-playing'); });
  video.addEventListener('pause', () => { set('Lecture en pause'); panel.classList.remove('is-playing'); });
  video.addEventListener('ended', () => { set('Vidéo terminée · vous pouvez la revoir'); panel.classList.remove('is-playing'); });
  video.addEventListener('error', () => set('La vidéo ne peut pas être chargée. Réessayez ou utilisez le lien sous l’article.'));
  // One playback at a time; no automatic restart when returning to the tab.
  video.addEventListener('play', () => document.querySelectorAll('video').forEach(other => { if (other !== video) other.pause(); }));
  document.addEventListener('visibilitychange', () => { if (document.hidden && !video.paused) video.pause(); });
});

const batch = document.querySelector('#request-batch');
if (batch) {
  const all = batch.querySelector('[data-request-all]');
  const boxes = [...batch.querySelectorAll('input[name="request_ids[]"]')];
  const count = batch.querySelector('[data-selection-count]');
  const update = () => {
    const selected = boxes.filter(box => box.checked).length;
    count.textContent = `${selected} dossier${selected === 1 ? '' : 's'} sélectionné${selected === 1 ? '' : 's'}`;
    all.checked = selected === boxes.length && selected > 0;
    all.indeterminate = selected > 0 && selected < boxes.length;
  };
  all.addEventListener('change', () => { boxes.forEach(box => { box.checked = all.checked; }); update(); });
  boxes.forEach(box => box.addEventListener('change', update));
  batch.addEventListener('submit', event => {
    if (!boxes.some(box => box.checked)) {
      event.preventDefault(); count.textContent = 'Sélectionnez au moins un dossier.'; boxes[0]?.focus();
    }
  });
  update();
}
document.querySelector('[data-copy-email]')?.addEventListener('click', async event => {
  const feedback = document.querySelector('.request-tool-feedback');
  try { await navigator.clipboard.writeText(event.currentTarget.dataset.copyEmail); feedback.textContent = 'Adresse e-mail copiée.'; }
  catch { feedback.textContent = 'La copie est indisponible. Sélectionnez l’adresse dans les coordonnées du dossier.'; }
});
document.querySelector('[data-print-request]')?.addEventListener('click', () => window.print());

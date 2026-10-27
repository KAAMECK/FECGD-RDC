document.querySelectorAll('[data-confirm]').forEach(button => button.addEventListener('click', event => { if (!confirm(button.dataset.confirm)) event.preventDefault(); }));
const editor = document.querySelector('#visual-editor');
const form = document.querySelector('#content-form');
let dirty = false;
if (editor && form) {
  let selection;
  const remember = () => { const s = window.getSelection(); if (s.rangeCount && editor.contains(s.anchorNode)) selection = s.getRangeAt(0).cloneRange(); };
  editor.addEventListener('keyup', remember); editor.addEventListener('mouseup', remember);
  document.querySelectorAll('.toolbar button').forEach(button => button.addEventListener('click', () => {
    editor.focus(); if (selection) { const s = window.getSelection(); s.removeAllRanges(); s.addRange(selection); }
    if (button.hasAttribute('data-link')) { const link = prompt('Adresse du lien (https://…)'); if (link && /^https?:\/\//i.test(link)) document.execCommand('createLink', false, link); }
    else if (button.dataset.block) document.execCommand('formatBlock', false, button.dataset.block);
    else document.execCommand(button.dataset.command, false, null);
    dirty = true; remember();
  }));
  editor.addEventListener('paste', event => { event.preventDefault(); document.execCommand('insertText', false, event.clipboardData.getData('text/plain')); });
  form.addEventListener('input', () => { dirty = true; });
  form.addEventListener('submit', () => { document.querySelector('#content-value').value = editor.innerHTML; dirty = false; });
  window.addEventListener('beforeunload', event => { if (dirty) { event.preventDefault(); event.returnValue = ''; } });
  const list = document.querySelector('#image-list'); let dragged;
  list?.querySelectorAll('.photo').forEach(photo => {
    photo.addEventListener('dragstart', () => { dragged = photo; });
    photo.addEventListener('dragover', event => event.preventDefault());
    photo.addEventListener('drop', event => { event.preventDefault(); if (dragged && dragged !== photo) { list.insertBefore(dragged, photo); dirty = true; } });
    photo.querySelectorAll('[data-move]').forEach(button => button.addEventListener('click', () => { if (button.dataset.move === 'up' && photo.previousElementSibling) list.insertBefore(photo, photo.previousElementSibling); else if (button.dataset.move === 'down' && photo.nextElementSibling) list.insertBefore(photo.nextElementSibling, photo); dirty = true; }));
  });
}

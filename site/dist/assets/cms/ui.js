// Keep browser validation, with a readable error beside the relevant field.
document.querySelectorAll('.fecgd-form').forEach(form => {
  let firstInvalid = null;
  const fields = form.querySelectorAll('input:not([type=hidden]), select, textarea');
  fields.forEach((field, index) => {
    if (field.closest('.trap') || field.disabled) return;
    const error = document.createElement('span');
    error.className = 'field-error'; error.id = `field-error-${index}`;
    field.setAttribute('aria-describedby', [field.getAttribute('aria-describedby'), error.id].filter(Boolean).join(' '));
    if (field.type === 'checkbox' && field.closest('.consent')?.querySelector('span')) {
      field.closest('.consent').querySelector('span').append(error);
    } else field.insertAdjacentElement('afterend', error);
    const show = () => {
      const valid = field.validity.valid;
      field.setAttribute('aria-invalid', String(!valid));
      error.textContent = valid ? '' : field.validationMessage;
    };
    field.addEventListener('invalid', () => {
      show();
      if (!firstInvalid) {
        firstInvalid = field;
        requestAnimationFrame(() => { firstInvalid.focus(); firstInvalid = null; });
      }
    });
    field.addEventListener('input', () => { if (field.hasAttribute('aria-invalid')) show(); });
    field.addEventListener('change', () => { if (field.hasAttribute('aria-invalid')) show(); });
  });
});

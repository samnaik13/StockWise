document.addEventListener('click', function (event) {
  const add = event.target.closest('[data-add-row]');
  if (add) {
    const type = add.dataset.addRow;
    const container = document.getElementById(type + '-lines');
    const first = container?.querySelector('.item-row');
    if (!first) return;
    const clone = first.cloneNode(true);
    clone.querySelectorAll('input').forEach(input => { input.value = ''; });
    clone.querySelectorAll('select').forEach(select => { select.selectedIndex = 0; });
    container.appendChild(clone);
  }
  const remove = event.target.closest('.remove-row');
  if (remove) {
    const container = remove.closest('.line-items')?.querySelector('[id$="-lines"]');
    const rows = container?.querySelectorAll('.item-row') || [];
    if (rows.length > 1) remove.closest('.item-row').remove();
    else alert('At least one product line is required.');
  }
});

/**
 * GROCERY STORE INVENTORY MANAGEMENT SYSTEM
 * Vanilla JavaScript for Dynamic Multi-Item Forms, Calculation & Validation
 */

document.addEventListener('DOMContentLoaded', () => {

    // --------------------------------------------------------------------------
    // 1. DYNAMIC MULTI-ITEM ROW MANAGEMENT (PURCHASE & SALES FORMS)
    // --------------------------------------------------------------------------
    const itemsTableBody = document.getElementById('items-tbody');
    const addItemBtn = document.getElementById('add-item-btn');
    const grandTotalSpan = document.getElementById('grand-total-amount');

    function calculateTotals() {
        if (!itemsTableBody) return;
        let grandTotal = 0;
        const rows = itemsTableBody.querySelectorAll('tr.item-row');

        rows.forEach(row => {
            const qtyInput = row.querySelector('.item-qty');
            const priceInput = row.querySelector('.item-price');
            const totalCell = row.querySelector('.item-subtotal');

            const qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
            const price = parseFloat(priceInput ? priceInput.value : 0) || 0;
            const subtotal = qty * price;

            if (totalCell) {
                totalCell.textContent = '₹' + subtotal.toFixed(2);
            }
            grandTotal += subtotal;
        });

        if (grandTotalSpan) {
            grandTotalSpan.textContent = '₹' + grandTotal.toFixed(2);
        }
    }

    function populateSaleProductSelect(productSelect, selectedCatId, currentVal = '') {
        if (!window.ALL_SALE_PRODUCTS) return;
        productSelect.innerHTML = '<option value="">-- Choose Product to Sell --</option>';

        let filtered = window.ALL_SALE_PRODUCTS;
        if (selectedCatId && selectedCatId !== '') {
            filtered = filtered.filter(p => String(p.category_id) === String(selectedCatId));
        }

        let currCatName = null;
        let optgroup = null;

        filtered.forEach(p => {
            if (!selectedCatId || selectedCatId === '') {
                if (p.category_name !== currCatName) {
                    currCatName = p.category_name;
                    optgroup = document.createElement('optgroup');
                    optgroup.label = currCatName;
                    productSelect.appendChild(optgroup);
                }
            }

            const opt = document.createElement('option');
            opt.value = p.product_id;
            const availStock = parseFloat(p.quantity_available) || 0;
            opt.textContent = `${p.product_name} (Avail: ${availStock.toFixed(3).replace(/\.?0+$/, '')} ${p.unit})`;
            opt.setAttribute('data-category', p.category_id);
            opt.setAttribute('data-unit', p.unit);
            opt.setAttribute('data-price', p.selling_price);
            opt.setAttribute('data-stock', p.quantity_available);

            if (availStock <= 0) {
                opt.disabled = true;
                opt.style.color = '#adb5bd';
            }

            if (String(p.product_id) === String(currentVal)) {
                opt.selected = true;
            }

            if (optgroup && (!selectedCatId || selectedCatId === '')) {
                optgroup.appendChild(opt);
            } else {
                productSelect.appendChild(opt);
            }
        });
    }

    if (itemsTableBody) {
        itemsTableBody.addEventListener('input', (e) => {
            if (e.target.classList.contains('item-qty') || e.target.classList.contains('item-price')) {
                calculateTotals();
            }
        });

        itemsTableBody.addEventListener('change', (e) => {
            // Category change in Counter Sales Billing
            if (e.target.classList.contains('category-select')) {
                const catSelect = e.target;
                const row = catSelect.closest('tr');
                const prodSelect = row.querySelector('.item-select');
                if (prodSelect) {
                    populateSaleProductSelect(prodSelect, catSelect.value, '');
                    const unitCell = row.querySelector('.item-unit');
                    if (unitCell) unitCell.textContent = '-';
                    const priceInput = row.querySelector('.item-price');
                    if (priceInput) priceInput.value = '';
                    const stockSpan = row.querySelector('.item-stock-hint');
                    if (stockSpan) stockSpan.textContent = '';
                    const subtotalCell = row.querySelector('.item-subtotal');
                    if (subtotalCell) subtotalCell.textContent = '₹0.00';
                    calculateTotals();
                }
            }

            // Product change
            if (e.target.classList.contains('item-select')) {
                const selectedOption = e.target.options[e.target.selectedIndex];
                const row = e.target.closest('tr');
                const unitCell = row.querySelector('.item-unit');
                const priceInput = row.querySelector('.item-price');
                const stockSpan = row.querySelector('.item-stock-hint');
                const catSelect = row.querySelector('.category-select');

                if (selectedOption) {
                    const prodCat = selectedOption.getAttribute('data-category');
                    if (catSelect && prodCat && catSelect.value === '') {
                        catSelect.value = prodCat;
                    }

                    const unit = selectedOption.getAttribute('data-unit') || '';
                    const price = selectedOption.getAttribute('data-price') || '';
                    const stock = selectedOption.getAttribute('data-stock') || '';

                    if (unitCell) unitCell.textContent = unit;
                    if (priceInput && price !== '') priceInput.value = parseFloat(price).toFixed(2);
                    if (stockSpan) stockSpan.textContent = stock !== '' ? `(Avail: ${stock} ${unit})` : '';
                }

                calculateTotals();
            }
        });

        itemsTableBody.addEventListener('click', (e) => {
            if (e.target.closest('.remove-row-btn')) {
                const rows = itemsTableBody.querySelectorAll('tr.item-row');
                if (rows.length > 1) {
                    e.target.closest('tr').remove();
                    calculateTotals();
                } else {
                    alert('At least one item line is required for this transaction.');
                }
            }
        });
    }

    if (addItemBtn && itemsTableBody) {
        addItemBtn.addEventListener('click', () => {
            const firstRow = itemsTableBody.querySelector('tr.item-row');
            if (!firstRow) return;

            const newRow = firstRow.cloneNode(true);
            newRow.querySelectorAll('input').forEach(input => {
                if (input.type === 'number' || input.type === 'text') input.value = '';
            });
            newRow.querySelectorAll('select').forEach(select => {
                select.selectedIndex = 0;
            });

            // If sales form, re-initialize product select to show all optgroups
            if (window.ALL_SALE_PRODUCTS) {
                const prodSelect = newRow.querySelector('.item-select');
                if (prodSelect) {
                    populateSaleProductSelect(prodSelect, '');
                }
            }

            const unitCell = newRow.querySelector('.item-unit');
            if (unitCell) unitCell.textContent = '-';

            const subtotalCell = newRow.querySelector('.item-subtotal');
            if (subtotalCell) subtotalCell.textContent = '₹0.00';

            const stockSpan = newRow.querySelector('.item-stock-hint');
            if (stockSpan) stockSpan.textContent = '';

            itemsTableBody.appendChild(newRow);
            calculateTotals();
        });
    }

    // --------------------------------------------------------------------------
    // 2. CLIENT-SIDE SALES PRE-VALIDATION (PREVENT OVERSELLING IN BROWSER)
    // --------------------------------------------------------------------------
    const saleForm = document.getElementById('sale-form');
    if (saleForm) {
        saleForm.addEventListener('submit', (e) => {
            let hasError = false;
            let errorMessage = '';

            const rows = itemsTableBody.querySelectorAll('tr.item-row');
            const selectedProductIds = new Set();

            rows.forEach((row, index) => {
                const select = row.querySelector('.item-select');
                const qtyInput = row.querySelector('.item-qty');
                if (!select || !qtyInput) return;

                const productId = select.value;
                if (!productId) {
                    hasError = true;
                    errorMessage = `Row ${index + 1}: Please select a grocery product.`;
                    return;
                }

                if (selectedProductIds.has(productId)) {
                    hasError = true;
                    errorMessage = `Duplicate product in row ${index + 1}. Please combine quantities into one row.`;
                    return;
                }
                selectedProductIds.add(productId);

                const requestedQty = parseFloat(qtyInput.value) || 0;
                if (requestedQty <= 0) {
                    hasError = true;
                    errorMessage = `Row ${index + 1}: Quantity must be greater than zero.`;
                    return;
                }

                const selectedOption = select.options[select.selectedIndex];
                const availableStock = parseFloat(selectedOption.getAttribute('data-stock')) || 0;
                const unit = selectedOption.getAttribute('data-unit') || '';
                const productName = selectedOption.text.split('(')[0].trim();

                if (requestedQty > availableStock) {
                    hasError = true;
                    errorMessage = `Stock Outflow Violation: Product '${productName}' only has ${availableStock} ${unit} available. You requested ${requestedQty} ${unit}.`;
                }
            });

            if (hasError) {
                e.preventDefault();
                alert(errorMessage);
            }
        });
    }

    // --------------------------------------------------------------------------
    // 3. DELETE CONFIRMATION DIALOGS
    // --------------------------------------------------------------------------
    document.querySelectorAll('.confirm-delete').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const name = btn.getAttribute('data-name') || 'this record';
            if (!confirm(`Are you sure you want to delete ${name}? This action cannot be undone.`)) {
                e.preventDefault();
            }
        });
    });

    // Supplier Force Delete Confirmation
    document.querySelectorAll('.confirm-delete-supplier').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const name = btn.getAttribute('data-name') || 'this supplier';
            const invoices = parseInt(btn.getAttribute('data-invoices') || '0', 10);
            if (invoices > 0) {
                const proceed = confirm(`Warning: Supplier '${name}' has ${invoices} linked purchase invoice(s).\n\nDeleting this supplier will also remove their related purchase records to satisfy database integrity.\n\nDo you want to proceed and permanently delete?`);
                if (!proceed) {
                    e.preventDefault();
                }
            } else {
                const proceed = confirm(`Are you sure you want to delete supplier '${name}'?`);
                if (!proceed) {
                    e.preventDefault();
                }
            }
        });
    });

    // --------------------------------------------------------------------------
    // 4. WHOLESALE SUPPLIER PRODUCT FILTERING (PURCHASE STOCK-IN FORM)
    // --------------------------------------------------------------------------
    const supplierSelect = document.getElementById('wholesale-supplier-select');
    const showAllToggle = document.getElementById('show-all-products-toggle');

    if (supplierSelect && window.ALL_PRODUCTS && window.SUPPLIER_PRODUCT_MAP) {
        function getFilteredProducts() {
            const sid = parseInt(supplierSelect.value, 10) || 0;
            const showAll = showAllToggle && showAllToggle.checked;

            if (showAll) {
                return window.ALL_PRODUCTS;
            }
            if (sid === 0) {
                return [];
            }
            const allowedProductIds = window.SUPPLIER_PRODUCT_MAP[sid] || [];
            return window.ALL_PRODUCTS.filter(p => allowedProductIds.includes(parseInt(p.product_id, 10)));
        }

        function refreshProductDropdowns(preserveSelection = true) {
            const sid = parseInt(supplierSelect.value, 10) || 0;
            const showAll = showAllToggle && showAllToggle.checked;
            const filtered = getFilteredProducts();

            const rows = document.querySelectorAll('#items-tbody tr.item-row');
            rows.forEach(row => {
                const select = row.querySelector('.item-select');
                if (!select) return;

                const currentVal = select.value;
                select.innerHTML = '';

                const defaultOpt = document.createElement('option');
                defaultOpt.value = '';

                if (!showAll && sid === 0) {
                    defaultOpt.textContent = '-- Choose Wholesale Supplier First --';
                } else if (!showAll && filtered.length === 0) {
                    defaultOpt.textContent = '-- No products linked to this wholesaler (check "Show all" to override) --';
                } else {
                    defaultOpt.textContent = '-- Choose Product --';
                }
                select.appendChild(defaultOpt);

                let isCurrentValid = false;
                filtered.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.product_id;
                    opt.textContent = p.product_name;
                    opt.setAttribute('data-unit', p.unit);
                    opt.setAttribute('data-price', p.average_cost);
                    opt.setAttribute('data-stock', p.quantity_available);
                    opt.setAttribute('data-supplier', p.supplier_id || '');

                    if (preserveSelection && String(p.product_id) === String(currentVal)) {
                        opt.selected = true;
                        isCurrentValid = true;
                    }
                    select.appendChild(opt);
                });

                if (preserveSelection && !isCurrentValid && currentVal !== '') {
                    select.value = '';
                    const unitCell = row.querySelector('.item-unit');
                    if (unitCell) unitCell.textContent = '-';
                    const priceInput = row.querySelector('.item-price');
                    if (priceInput) priceInput.value = '';
                    const subtotalCell = row.querySelector('.item-subtotal');
                    if (subtotalCell) subtotalCell.textContent = '₹0.00';
                }
            });

            calculateTotals();
        }

        supplierSelect.addEventListener('change', () => {
            refreshProductDropdowns(false);
        });

        if (showAllToggle) {
            showAllToggle.addEventListener('change', () => {
                refreshProductDropdowns(true);
            });
        }

        // Initialize on page load (preserves selection if reloaded with POST data)
        refreshProductDropdowns(true);
    }

    // Run initial calculation
    calculateTotals();
});


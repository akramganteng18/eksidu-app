import Alpine from 'alpinejs';
import themeToggle from './theme';
import { toasts, confirmDialog, initLoading, initCountUp, fileField } from './ui';

window.Alpine = Alpine;

Alpine.data('themeToggle', themeToggle);
Alpine.data('toasts', toasts);
Alpine.data('confirmDialog', confirmDialog);
Alpine.data('fileField', fileField);

initLoading();
function initResponsiveTables() {
    document.querySelectorAll('.tbl-cards-mobile').forEach((table) => {
        const labels = Array.from(table.querySelectorAll('thead th')).map((th) => th.textContent.trim());
        table.querySelectorAll('tbody tr').forEach((row) => {
            Array.from(row.children).forEach((cell, index) => {
                if (cell.tagName === 'TD' && !cell.hasAttribute('data-label') && !cell.hasAttribute('colspan')) {
                    cell.setAttribute('data-label', labels[index] || 'Informasi');
                }
            });
        });
    });
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { initCountUp(); initResponsiveTables(); });
} else {
    initCountUp();
    initResponsiveTables();
}

Alpine.start();

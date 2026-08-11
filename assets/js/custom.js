// ============================================
// FICHIER : assets/js/custom.js
// RÔLE : Scripts personnalisés
// ============================================

// Confirmation de suppression
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.btn-delete-confirm');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible.')) {
                e.preventDefault();
            }
        });
    });

    // Initialisation des tooltips Bootstrap
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    if (tooltips.length > 0) {
        const tooltipList = [...tooltips].map(t => new bootstrap.Tooltip(t));
    }

    // Auto-dissmiss des alertes après 5 secondes
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) closeBtn.click();
        }, 5000);
    });
});

// Fonction pour formater les dates en JavaScript
function formaterDateFr(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

// Fonction pour afficher un indicateur de chargement
function showLoading(btn) {
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Chargement...';
    btn.disabled = true;
}

function hideLoading(btn, originalText) {
    btn.innerHTML = originalText;
    btn.disabled = false;
}
$(document).ready(function() {
    // Fonction pour remplir les informations du personal
    function fillPersonalInfo(personalId) {
        // Réinitialiser les champs si aucune sélection
        if (!personalId || personalId === '') {
            $('#conge_name').val('');
            $('#conge_hireDate').val('');
            $('#conge_category').val('');
            return;
        }

        // Appeler l'API pour récupérer les informations du personal
        $.ajax({
            url: '/dossier/personal/conge/personal/' + personalId + '/info',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                // Remplir les champs avec les données reçues
                $('#conge_name').val(data.name || '');
                $('#conge_hireDate').val(data.hireDate || '');
                $('#conge_category').val(data.category || '');
            },
            error: function(xhr, status, error) {
                console.error('Erreur lors de la récupération des informations du personal:', error);
                // Réinitialiser les champs en cas d'erreur
                $('#conge_name').val('');
                $('#conge_hireDate').val('');
                $('#conge_category').val('');
            }
        });
    }

    // Attendre que Select2 soit initialisé (si le plugin customselect est utilisé)
    setTimeout(function() {
        const $personalSelect = $('#conge_personal');
        
        // Écouter le changement de sélection (fonctionne avec Select2 et select standard)
        $personalSelect.on('change', function() {
            fillPersonalInfo($(this).val());
        });
        
        // Écouter aussi l'événement Select2 spécifique (si Select2 est utilisé)
        $personalSelect.on('select2:select', function(e) {
            fillPersonalInfo(e.params.data.id);
        });
    }, 100);
});
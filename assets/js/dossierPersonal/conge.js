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
        
        // Vérifier si on est sur la page de modification (si le select a déjà une valeur)
        const selectedPersonalId = $personalSelect.val();
        if (selectedPersonalId && selectedPersonalId !== '') {
            // Remplir automatiquement les informations au chargement de la page
            fillPersonalInfo(selectedPersonalId);
        }
        
        // Écouter le changement de sélection (fonctionne avec Select2 et select standard)
        $personalSelect.on('change', function() {
            fillPersonalInfo($(this).val());
        });
        
        // Écouter aussi l'événement Select2 spécifique (si Select2 est utilisé)
        $personalSelect.on('select2:select', function(e) {
            fillPersonalInfo(e.params.data.id);
        });
        
        // Écouter l'événement Select2 ready (quand Select2 est complètement initialisé)
        $personalSelect.on('select2:ready', function() {
            const selectedId = $personalSelect.val();
            if (selectedId && selectedId !== '') {
                fillPersonalInfo(selectedId);
            }
        });
    }, 100);
    
    // Attendre un peu plus longtemps pour s'assurer que tous les plugins sont chargés
    setTimeout(function() {
        const $personalSelect = $('#conge_personal');
        const selectedPersonalId = $personalSelect.val();
        if (selectedPersonalId && selectedPersonalId !== '' && 
            ($('#conge_name').val() === '' || $('#conge_category').val() === '')) {
            // Si les champs ne sont pas remplis mais qu'un personal est sélectionné, remplir les infos
            fillPersonalInfo(selectedPersonalId);
        }
    }, 500);
});
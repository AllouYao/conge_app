$(document).on('click', '#btn_reporting_etat_salaire', function() {
    const dateDebut = $('#example-month').val();
    $('#printEtatSalaireInput').val(dateDebut);

    const personalId = $('#personalsId').val();
    $('#personalsIdInput').val(personalId);

}); 
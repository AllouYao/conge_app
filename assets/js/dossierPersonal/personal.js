// Calcule total sole assurance
/**
$(document).ready(function () {
    $('body').on('change', '#assurance_personal_chargePeople, #assurance_personal_retenuForfetaire', function () {
        const selectElement = $('#assurance_personal_chargePeople option:selected');
        const selectRetenue = $('#assurance_personal_retenuForfetaire option:selected');
        const $amountRetenue = +selectRetenue.attr('data-value');
        const nbPeople = +selectElement.length;
        const soldeAssurance = $('#assurance_personal_amount')
        if (selectRetenue.attr('data-code') === 'ASSURANCE_SANTE_FAMILLE_SALARIALE') {
            console.log(nbPeople)
            if (nbPeople <= 3) {
                soldeAssurance.val($amountRetenue)
            }
        }
    })
})
**/


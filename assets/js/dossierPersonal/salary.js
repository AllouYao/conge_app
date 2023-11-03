$(`#personal_contract_dateEmbauche, #personal_ancienity`).each(function () {
    let $dateEmbauche = $('#personal_contract_dateEmbauche').val();
    $('#personal_ancienity').val(0)
    let $today = new Date();
    let date = new Date($dateEmbauche)
    let $annee = $today.getFullYear()
    let $anneA = date.getFullYear()
    let $anciennete = $annee - $anneA;
    $('#personal_ancienity').val($anciennete);
});
let int = new Intl.NumberFormat("fr-FR", {maximumFractionDigits: 0});


let totalAmount = 0;
let amount = 0;
let amountAventage = 0;
let sursalaire = 0;
let transport = 0;
let primeFonction = 0;
let primeLogement = 0;
let indemniteFonction = 0;
let indemniteLogement = 0;
let totalPrime = 0;
let totalBrutImpo = 0;


let salaireCategoriel = () => {
    const $selected = $("#personal_categorie :selected");
    amount = +$selected.attr('data-amount') || 0;

    const $selectAventage = $('#personal_salary_avantageNature :selected');
    amountAventage = +$selectAventage.attr('data-amount-nature') || 0;

    totalAmount = totalPrime + amount  + amountAventage;

    $('#personal_salary_baseAmount').val(amount)
    $('#personal_salary_brutAmount').val(totalAmount)
    if (transport >= 30000) {
        totalBrutImpo = totalAmount - 30000
        $('#personal_salary_brutImposable').val(totalBrutImpo)
    } else {
        $('#personal_salary_brutImposable').val(totalAmount)
    }

};

let calculateSalary = () => {
    sursalaire = +$('#personal_salary_sursalaire').val() || 0;
    transport = +$('#personal_salary_primeTransport').val() || 0;
    primeFonction = +$('#personal_salary_primeFonction').val() || 0;
    primeLogement = +$('#personal_salary_primeLogement').val() || 0;
    indemniteFonction = +$('#personal_salary_indemniteFonction').val() || 0;
    indemniteLogement = +$('#personal_salary_indemniteLogement').val() || 0;
    totalPrime = sursalaire + transport + primeFonction + primeLogement + indemniteFonction + indemniteLogement;
    salaireCategoriel()
}

let getAnciennete = () => {
    $('body').on('change', '#personal_contract_dateEmbauche, #personal_ancienity', function () {
        let $dateEmbauche = $('#personal_contract_dateEmbauche').val();
        let $today = new Date();
        let date = new Date($dateEmbauche)
        let $annee = $today.getFullYear()
        let $anneA = date.getFullYear()

        let $anciennete = $annee - $anneA;
        $('#personal_ancienity').val($anciennete);
    })
}
$('body').on('input', '#personal_salary_primeTransport, #personal_salary_sursalaire, #personal_salary_primeFonction, #personal_salary_primeLogement, #personal_salary_indemniteFonction, #personal_salary_indemniteLogement', function () {
    calculateSalary()
})

$('body').on('change', '#personal_categorie, #personal_salary_primes, #personal_salary_avantageNature', function () {
    salaireCategoriel()
})


salaireCategoriel()
calculateSalary()
getAnciennete()
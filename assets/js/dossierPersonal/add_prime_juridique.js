import {runInputmask} from "../inputmask";

//Mais variable  globals
let montantTotal = 0;
let $amount = 0;
let $amountAventage = 0;
let $montantNonJuridique = 0;
let lastIndexDemandeAchat = 0;

let sursalaire = 0;
let transport = 0;
let fonction = 0;
let logement = 0;

let $anciennete = 0;


$('#add-collection-widget-personal-salary-prime').click(function () {
    const list = $($(this).attr('data-list-selector'));
    const $widget = $('#widget-counter-personal-salary-prime');
    const index = +$widget.val();
    // Try to find the counter of the list or use the length of the list

    // grab the prototype template
    let newWidget = list.attr('data-prototype');
    // replace the "__name__" used in the id and name of the prototype
    // with a number that's unique to your emails
    // end name attribute looks like name="contact[emails][2]"
    newWidget = newWidget.replace(/__name__/g, index);
    // Increase the counter
    $widget.val(index + 1);
    // And store it, the length cannot be used if deleting widgets is allowed
    list.data('widget-counter-personal-salary-prime', index);
    lastIndexDemandeAchat = index;
    // create a new list element and add it to the list
    $('#detail_personal_prime_table tbody').append(newWidget);
    addTagFormDeleteLinkPrime(newWidget);
    //$('select.select2').select2({width: '100%', theme: 'bootstrap'});
    $('[data-plugin="customselect"]').select2();
    runInputmask()
});
const addTagFormDeleteLinkPrime = () => {
    $('body').on('click', '.delete-personal-salary-prime', function () {
        const target = $(this).attr('data-target');
        $(target).remove();
        calculateTotalPrimeJuridique()
        calculateSalaireNet()
    });
};


let calculatePrimeJuridique = () => {
    $('body').on('change', '.prime-salary', function () {
        const parentId = $(this).parent().parent().attr('data-id');
        const $prime = +$(`#${parentId}_prime option:selected`).attr('data-taux');
        const coefficient = $(`#${parentId}_taux`);
        const primes = $(`#${parentId}_prime`).val()

        if (primes.length > 0) {
            coefficient.val($prime);
            const $smig = +$('#personal_salary_smig').val();
            let $horaire = $smig / 173.33;
            $(`#${parentId}_smigHoraire`).val($horaire);
        } else {
            $(`#${parentId}_smigHoraire`).val(' ');
            coefficient.val(' ');
        }

        const $taux = +$(`#${parentId}_taux`).val();
        const $smigHoraire = +$(`#${parentId}_smigHoraire`).val();
        let $montant = $smigHoraire * $taux;
        $(`#${parentId}_amountPrime`).val($montant)
        calculateTotalPrimeJuridique()
        calculateSalaireNet()
    })
}
const calculateTotalPrimeJuridique = () => {
    const sum = [];
    $('.amount-total-prime').each(function () {
        sum.push(+$(this).val());
    });
    if (sum.length > 0) {
        montantTotal = sum.reduce((previousValue, currentValue) => previousValue + currentValue);
    } else {
        montantTotal = 0;
    }
    $('#total_montant_prime_salary').html(new Intl.NumberFormat('fr-FR').format(montantTotal || 0));
}


const salaireBase = () => {
    $('body').on('change', '#personal_categorie', function () {
        const $selected = $("#personal_categorie :selected");
        $amount = +$selected.attr('data-amount') || 0;
        calculateSalaireNet()
    });
}


const avantageNature = () => {
    $('body').on('change', '#personal_salary_avantage', function () {
        const $selectAventage = $('#personal_salary_avantage :selected');
        $amountAventage = +$selectAventage.attr('data-total-avantage') || 0;
        calculateSalaireNet()
    })
}


const calculatePrimeNonJuridique = () => {
    $('body').on('input', '.total-prime', function () {
        sursalaire = +$('#personal_salary_sursalaire').val();
        transport = +$('#personal_salary_primeTransport').val();
        fonction = +$('#personal_salary_primeFonction').val();
        logement = +$('#personal_salary_primeLogement').val();
        $montantNonJuridique = sursalaire + transport + fonction + logement
        console.log('montant prime non juridique: ', $montantNonJuridique)
        calculateSalaireNet()
    })
}


const calculateSalaireNet = () => {
    const Transport = 30000;
    let $transportImposable = 0;
    let $logementImposable = 0;
    console.log('Salaire de base: ', $amount)
    console.log('Total prime juridique: ', montantTotal)

    let montantNet = $amount + montantTotal + $montantNonJuridique;
    console.log('montant net :', montantNet)

    $('#personal_salary_baseAmount').val($amount);
    $('#personal_salary_brutAmount').val(montantNet)
    if (transport > Transport && logement > $amountAventage) {
        $transportImposable = transport - Transport;
        $logementImposable = logement - $amountAventage
        console.log('prime transport imposable: ', $transportImposable)
        console.log('prime logement imposable: ', $logementImposable)
        $('#personal_salary_brutImposable').val(montantNet - Transport + $logementImposable + $transportImposable)
    } else {
        $('#personal_salary_brutImposable').val(montantNet)
    }
}


let getAnciennete = () => {
    $('body').on('change', '#personal_contract_dateEmbauche, #personal_ancienity', function () {
        let $dateEmbauche = $('#personal_contract_dateEmbauche').val();
        let $today = new Date();
        let date = new Date($dateEmbauche)
        let $annee = $today.getFullYear()
        let $anneA = date.getFullYear()

        $anciennete = $annee - $anneA;
        $('#personal_ancienity').val($anciennete);
    })
}


$('.prime-salary').each(function () {
    const parentId = $(this).parent().parent().attr('data-id');
    const $prime = +$(`#${parentId}_prime option:selected`).attr('data-taux');
    const coefficient = $(`#${parentId}_taux`);
    const primes = $(`#${parentId}_prime`).val()

    if (primes.length > 0) {
        coefficient.val($prime);
        const $smig = +$('#personal_salary_smig').val();
        let $horaire = $smig / 173.33;
        $(`#${parentId}_smigHoraire`).val($horaire);
    } else {
        $(`#${parentId}_smigHoraire`).val(' ');
        coefficient.val(' ');
    }

    const $taux = +$(`#${parentId}_taux`).val();
    const $smigHoraire = +$(`#${parentId}_smigHoraire`).val();
    let $montant = $smigHoraire * $taux;
    $(`#${parentId}_amountPrime`).val($montant);
    calculateTotalPrimeJuridique()
    calculateSalaireNet()
});

$('.total-prime').each(function () {
    sursalaire = +$('#personal_salary_sursalaire').val();
    transport = +$('#personal_salary_primeTransport').val();
    fonction = +$('#personal_salary_primeFonction').val();
    logement = +$('#personal_salary_primeLogement').val();
    $montantNonJuridique = sursalaire + transport + fonction + logement
    calculateSalaireNet()
});

$(`#personal_categorie`).each(function () {
    const $selected = $("#personal_categorie :selected");
    $amount = +$selected.attr('data-amount') || 0;
    calculateSalaireNet()
});

$(`#personal_salary_avantage`).each(function () {
    const $selectAventage = $('#personal_salary_avantage :selected');
    $amountAventage = +$selectAventage.attr('data-total-avantage') || 0;
    calculateSalaireNet()
});


$(document).ready(function () {
    $('#personal_ancienity').val(' ');
    let $anciennete = 0;
    $(`#personal_contract_dateEmbauche, #personal_ancienity`).each(function () {
        let $dateEmbauche = $('#personal_contract_dateEmbauche').val();
        let $today = new Date();
        let date = new Date($dateEmbauche)
        let $annee = $today.getFullYear()
        let $anneA = date.getFullYear()

        $anciennete = $annee - $anneA;
        $('#personal_ancienity').val($anciennete);
    });
})


getAnciennete()
salaireBase()
avantageNature()
calculatePrimeJuridique()
calculatePrimeNonJuridique()
calculateSalaireNet()
addTagFormDeleteLinkPrime()
calculateTotalPrimeJuridique()
runInputmask()

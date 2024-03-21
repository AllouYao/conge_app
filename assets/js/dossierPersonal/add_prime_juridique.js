import {runDecimalInputmask, runInputmask} from "../inputmask";
import {round} from "@popperjs/core/lib/utils/math";

//Mais variable  globals
let $amountPrimes = 0;
let $salaireBase = 0;
let $amountAventage = 0;
let $amountBrut = 0;
let $amountBrutImposable = 0;
let lastIndexDemandeAchat = 0;
let lastIndexAutrePrimes = 0;
let total = 0;

let sursalaire = 0;
let transport = 0;
let amountBrut = 0;

// debut pour la gestion de la collection des primes juridique
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
    runDecimalInputmask()
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
        let $horaire;
        if (primes.length > 0) {
            coefficient.val($prime);
            const $smig = +$('#personal_salary_smig').val();
            $horaire = $smig / 173.33;
            $(`#${parentId}_smigHoraire`).val($horaire);
        } else {
            $(`#${parentId}_smigHoraire`).val(' ');
            coefficient.val(' ');
        }

        const $taux = +$(`#${parentId}_taux`).val();
        let $montant = $horaire * $taux;
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
        $amountPrimes = sum.reduce((previousValue, currentValue) => previousValue + currentValue);
    } else {
        $amountPrimes = 0;
    }
    $('#total_montant_prime_salary').html(round($amountPrimes || 0));
}
// fin pour la gestion de la collection des primes juridique


const salaireBase = () => {
    $('body').on('change', '#personal_categorie', function () {
        const $selected = $("#personal_categorie :selected");
        $salaireBase = +$selected.attr('data-amount') || 0;
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
const calculatePrimes = () => {
    $('body').on('input', '.total-prime', function () {
        sursalaire = +$('#personal_salary_sursalaire').val();
        transport = +$('#personal_salary_primeTransport').val();
        calculateSalaireNet()
    })
}


// debut pour la gestion de la collection des autres primes
$('#add-collection-widget-personal-autre-prime').click(function () {
    const list = $($(this).attr('data-list-selector'));
    const $widget = $('#widget-counter-personal-autre-prime');
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
    list.data('widget-counter-personal-autre-prime', index);
    lastIndexAutrePrimes = index;
    // create a new list element and add it to the list
    $('#detail_personal_autre_primes_table tbody').append(newWidget);
    addTagFormDeleteLinkAutrePrime(newWidget);
    //$('select.select2').select2({width: '100%', theme: 'bootstrap'});
    $('[data-plugin="customselect"]').select2();
    runInputmask()
});
const addTagFormDeleteLinkAutrePrime = () => {
    $('body').on('click', '.delete-personal-autre-prime', function () {
        const target = $(this).attr('data-target');
        $(target).remove();
        calculateTotalAutrePrime()
        calculateSalaireNet()
    });
};

const prime = () => {
    $('body').on('change', '.autre-prime, .amount-autre-prime', function () {
        calculateTotalAutrePrime()
        calculateSalaireNet()
    });
}
const calculateTotalAutrePrime = () => {
    const sum = [];
    $('.amount-autre-prime').each(function () {
        sum.push(+$(this).val());
    });
    if (sum.length > 0) {
        total = sum.reduce((previousValue, currentValue) => previousValue + currentValue);
    } else {
        total = 0;
    }
    $('#total_autre_prime').html(new Intl.NumberFormat('fr-FR').format(total || 0));
}
// fin pour la gestion de la collection des autres primes


const calculateSalaireNet = () => {
    const DEFAULT_TRANSPORT = +$('#personal_salary_transportImposable').val();
    let $logements = +$('#personal_salary_amountAventage').val(); // cette est en realité la valeur de l'avantage en nature donner par l'employeur.
    let $transport = +$('#personal_salary_primeTransport').val();
    let $avantagesImposable = $logements > $amountAventage ? $logements - $amountAventage : 0;
    let $autrePrime = total;
    let $primeJuridique = $amountPrimes;
    amountBrut = $salaireBase + sursalaire + total;

    let $amountImposableWithAvantage = $avantagesImposable !== 0 && $amountAventage !== 0 ? amountBrut + $avantagesImposable : amountBrut;
    let $transportImposable = $transport > DEFAULT_TRANSPORT ? $transport - DEFAULT_TRANSPORT : 0;
    let $amountImposableWithTransport = $transportImposable !== 0 && $transport > DEFAULT_TRANSPORT ? $transportImposable : 0;
    $amountBrut = amountBrut + $transport + $logements;


    $amountBrutImposable = $amountImposableWithAvantage + $amountImposableWithTransport;
    console.log('autre prime: ', $autrePrime)
    console.log('prime juridique: ', $primeJuridique)

    console.log('Montant brut: ', $amountBrut)
    console.log('Montant brut imposable: ', $amountBrutImposable)
    $('#personal_salary_baseAmount').val($salaireBase);
    $('#personal_salary_brutAmount').val($amountBrut);
    $('#personal_salary_brutImposable').val($amountBrutImposable);

};


$('.prime-salary').each(function () {
    const parentId = $(this).parent().parent().attr('data-id');
    const $prime = +$(`#${parentId}_prime option:selected`).attr('data-taux');
    const coefficient = $(`#${parentId}_taux`);
    const primes = $(`#${parentId}_prime`).val()

    if (primes.length) {
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
    calculateSalaireNet()
});

$(`#personal_categorie`).each(function () {
    const $selected = $("#personal_categorie :selected");
    $salaireBase = +$selected.attr('data-amount') || 0;
    calculateSalaireNet()
});

$(`#personal_salary_avantage`).each(function () {
    const $selectAventage = $('#personal_salary_avantage :selected');
    $amountAventage = +$selectAventage.attr('data-total-avantage') || 0;
    calculateSalaireNet()
});

prime();
calculateTotalAutrePrime()
addTagFormDeleteLinkAutrePrime()

salaireBase()
avantageNature()
calculatePrimeJuridique()
calculatePrimes()
calculateSalaireNet()
addTagFormDeleteLinkPrime()
calculateTotalPrimeJuridique()

runInputmask()
runDecimalInputmask()



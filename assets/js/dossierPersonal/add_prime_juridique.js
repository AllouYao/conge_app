import {runInputmask} from "../inputmask";

//Mais variable  globals
let $amountPrimes = 0;
let $salaireBase = 0;
let $amountAventage = 0;
let $amountBrut = 0;
let $amountBrutImposable = 0;
let lastIndexDemandeAchat = 0;

let sursalaire = 0;
let transport = 0;
let fonction = 0;
let logement = 0;
let amountBrut = 0;


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
        $amountPrimes = sum.reduce((previousValue, currentValue) => previousValue + currentValue);
    } else {
        $amountPrimes = 0;
    }
    $('#total_montant_prime_salary').html(new Intl.NumberFormat('fr-FR').format($amountPrimes || 0));
}


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


const calculatePrimeNonJuridique = () => {
    $('body').on('input', '.total-prime', function () {
        sursalaire = +$('#personal_salary_sursalaire').val();
        transport = +$('#personal_salary_primeTransport').val();
        fonction = +$('#personal_salary_primeFonction').val();
        logement = +$('#personal_salary_primeLogement').val();
        calculateSalaireNet()
    })
}


const calculateSalaireNet = () => {
    const DEFAULT_TRANSPORT = 30000;
    amountBrut = $salaireBase + $amountPrimes + sursalaire + fonction;
    let $logements = +$('#personal_salary_primeLogement').val();
    console.log('seconds logements: ', $logements)
    console.log('avantage en nature: ', $amountAventage)

    let $avantagesImposable = $logements > $amountAventage ? $logements - $amountAventage : 0;
    console.log('aventage imposable: ', $avantagesImposable)
    let $amountImposableWithAvantage = $avantagesImposable !== 0 && $amountAventage !== 0 ? amountBrut + logement + $avantagesImposable : amountBrut + logement;
    console.log('brut imposable avec avantage imposable: ', $amountImposableWithAvantage)

    let $transport = +$('#personal_salary_primeTransport').val();
    console.log('seconds transport: ', $transport)

    let $transportImposable = $transport > DEFAULT_TRANSPORT ? $transport - DEFAULT_TRANSPORT : 0;
    console.log('transport imposable: ', $transportImposable)
    let $amountImposableWithTransport = $transportImposable !== 0 && $transport > DEFAULT_TRANSPORT ? $transportImposable : DEFAULT_TRANSPORT;
    console.log('brut imposable avec transport imposable: ', $amountImposableWithTransport)

    $amountBrut = amountBrut + logement + $transport;
    console.log('Montant brut: ', $amountBrut)
    $amountBrutImposable = $amountImposableWithAvantage + $amountImposableWithTransport;
    console.log('Montant brut imposable: ', $amountBrutImposable)

    $('#personal_salary_baseAmount').val($salaireBase);
    $('#personal_salary_brutAmount').val($amountBrut)
    $('#personal_salary_brutImposable').val($amountBrutImposable);

};



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



salaireBase()
avantageNature()
calculatePrimeJuridique()
calculatePrimeNonJuridique()
calculateSalaireNet()
addTagFormDeleteLinkPrime()
calculateTotalPrimeJuridique()
runInputmask()

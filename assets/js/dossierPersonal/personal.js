let $horaire = 0;
let int = new Intl.NumberFormat("fr-FR", {maximumFractionDigits: 0});
let getPrimeCoefficient = () => {
    $('body').on('change', '.prime-salary', function (e) {
        const parentId = $(this).parent().parent().attr('data-id');
        const $prime = +$(`#${parentId}_prime option:selected`).attr('data-taux');
        const coefficient = $(`#${parentId}_taux`);
        const primes = $(`#${parentId}_prime`).val()
        console.log(primes)
        if (primes.length > 0) {
            coefficient.val($prime);
            const $smig = +$('#personal_salary_smig').val();
            $horaire = $smig / 173.33;
            $(`#${parentId}_smigHoraire`).val($horaire);
        }else {
            $(`#${parentId}_smigHoraire`).val(' ');
            coefficient.val(' ');
        }

        calculateSmigHoraire(parentId)
    })
}

let calculateSmigHoraire = (parentId) => {
    const $taux = +$(`#${parentId}_taux`).val();
    const $smigHoraire = +$(`#${parentId}_smigHoraire`).val();
    let montant = $smigHoraire * $taux;
    $(`#${parentId}_amountPrime`).val(montant)
    calculTotalIntentionAchat()
}

const calculTotalIntentionAchat = () => {
    const sum = [];
    $('.amount-total-prime').each(function () {
        sum.push(+$(this).val());
    });
    let montantTotal;
    if (sum.length > 0) {
        montantTotal = sum.reduce((previousValue, currentValue) => previousValue + currentValue);
        console.log(montantTotal)
    } else {
        montantTotal = 0;
    }
    $('#montant_prime_salary').html(new Intl.NumberFormat('fr-FR').format(montantTotal));
};

calculateSmigHoraire()
getPrimeCoefficient()
calculTotalIntentionAchat()

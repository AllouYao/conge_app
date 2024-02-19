import {runInputmask} from "../inputmask";

let total
let lastIndexRetenueForfetaire
// debut pour la gestion de la collection des autres primes
$('#add-collection-widget-personal-retenue-forfaitaire').click(function () {
    const list = $($(this).attr('data-list-selector'));
    const $widget = $('#widget-counter-personal-retenue-forfaitaire');
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
    list.data('widget-counter-personal-retenue-forfaitaire', index);
    lastIndexRetenueForfetaire = index;
    // create a new list element and add it to the list
    $('#detail_personal_retenue_forfaitaire_table tbody').append(newWidget);
    addTagFormDeleteLinkRetenueForfaitaire(newWidget);
    //$('select.select2').select2({width: '100%', theme: 'bootstrap'});
    $('[data-plugin="customselect"]').select2();
    runInputmask()
});
const addTagFormDeleteLinkRetenueForfaitaire = () => {
    $('body').on('click', '.delete-personal-retenue-forfaitaire', function () {
        const target = $(this).attr('data-target');
        $(target).remove();
        calculateTotalRetenueForfaitaire()
    });
};

let getRetenueForfetaire = () => {
    $('body').on('change', '.retenue-salary', function () {
        const parentId = $(this).parent().parent().attr('data-id');
        const $retenueForfetaire = +$(`#${parentId}_retenuForfetaire option:selected`).attr('data-value');
        const retenueForfetaire = $(`#${parentId}_retenuForfetaire`).val();
        const $amount = $(`#${parentId}_amount`);

        if (retenueForfetaire.length > 0) {
            $amount.val($retenueForfetaire)
        } else {
            $amount.val(' ');
        }

        $(`#${parentId}_amount`).val()
        calculateTotalRetenueForfaitaire()
    })
}

const calculateTotalRetenueForfaitaire = () => {
    const sum = [];
    $('.amount-retenue-forfaitaire').each(function () {
        sum.push(+$(this).val());
    });
    if (sum.length > 0) {
        total = sum.reduce((previousValue, currentValue) => previousValue + currentValue);
    } else {
        total = 0;
    }
    $('#total_montant_retenue_forfaitaire_salary').html(new Intl.NumberFormat('fr-FR').format(total || 0));
}
const retenueForfaitaire = () => {
    $('body').on('change', '.retenue-salary, .amount-retenue-forfaitaire', function () {
        calculateTotalRetenueForfaitaire()
    });
}
$('.retenue-salary').each(function () {
    const parentId = $(this).parent().parent().attr('data-id');
    const $retenueForfetaire = +$(`#${parentId}_retenuForfetaire option:selected`).attr('data-value');
    const retenueForfetaire = $(`#${parentId}_retenuForfetaire`).val();
    const $amount = $(`#${parentId}_amount`);

    if (retenueForfetaire.length > 0) {
        $amount.val($retenueForfetaire)
    } else {
        $amount.val(' ');
    }

    $(`#${parentId}_amount`).val()
    calculateTotalRetenueForfaitaire()
});

retenueForfaitaire()
calculateTotalRetenueForfaitaire()
getRetenueForfetaire()
addTagFormDeleteLinkRetenueForfaitaire()
$(document).ready(function () {
    $('.amount-month').hide();
    $('.nb-month').hide();
})

const typeOperation = $('#acompte_typeOperations');

typeOperation.change(function () {
    const val = $('#acompte_typeOperations option:selected').val();
    if (val === 'PRET') {
        $('.amount-month').show();
        $('.nb-month').show();
    } else {
        $('.amount-month').hide();
        $('.nb-month').hide();
    }
})


$('body').on('input', '#acompte_nbMensualite', '#acompte_amount', function () {
    const nb = +$(this).val();
    const amount = +$('#acompte_amount').val();
    $('#acompte_amountMensualite').val(amount / nb);
});

$(document).ready(function () {
    const typOp = typeOperation.children('option:selected').val();
    if (typOp === 'PRET') {
        $('.amount-month').show();
        $('.nb-month').show();
    } else {
        $('.amount-month').hide();
        $('.nb-month').hide();
    }
})

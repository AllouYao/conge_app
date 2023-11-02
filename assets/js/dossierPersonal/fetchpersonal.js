let accountPersonal = () => {
    const selectedOption = $("#account_personal :selected");
    const name = selectedOption.attr('data-name');
    const hireDate = selectedOption.attr('data-hireDate');
    const category = selectedOption.attr('data-category');

    $('#account_name').val(name);
    $('#account_hireDate').val(hireDate);
    $('#account_category').val(category);
}
let chargePeople = () => {
    const selectedOption = $("#charge_personal :selected");
    const name = selectedOption.attr('data-name');
    const hireDate = selectedOption.attr('data-hireDate');
    const category = selectedOption.attr('data-category');

    $('#charge_name').val(name);
    $('#charge_hireDate').val(hireDate);
    $('#charge_category').val(category);
}

let depart = () => {
    const selectedOption = $("#depart_personal :selected");
    const name = selectedOption.attr('data-name');
    const hireDate = selectedOption.attr('data-hireDate');
    const category = selectedOption.attr('data-category');

    $('#depart_name').val(name);
    $('#depart_hireDate').val(hireDate);
    $('#depart_category').val(category);
}
$('body').on('change', '#charge_personal, #account_personal, #depart_personal', function () {
    if ($(this).attr('id') === 'charge_personal') {
        chargePeople();
    } else if ($(this).attr('id') === 'account_personal') {
        accountPersonal();
    } else if ($(this).attr('id') === 'depart_personal') {
        depart();
    }
});

chargePeople();
accountPersonal();
depart();
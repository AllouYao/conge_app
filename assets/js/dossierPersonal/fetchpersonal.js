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

let conge = () => {
    const selectedOption = $("#conge_personal :selected");
    const name = selectedOption.attr('data-name');
    const hireDate = selectedOption.attr('data-hireDate');
    const category = selectedOption.attr('data-category');

    $('#conge_name').val(name);
    $('#conge_hireDate').val(hireDate);
    $('#conge_category').val(category);
}

let heureSupp = () => {
    const selectedOption = $("#personal_heure_sup_personal :selected");
    const name = selectedOption.attr('data-name');
    const hireDate = selectedOption.attr('data-hireDate');
    const category = selectedOption.attr('data-category');

    $('#personal_heure_sup_name').val(name);
    $('#personal_heure_sup_hireDate').val(hireDate);
    $('#personal_heure_sup_category').val(category);
}

let depart = () => {
    const selectedOption = $("#departure_personal :selected");
    const name = selectedOption.attr('data-name');
    const hireDate = selectedOption.attr('data-hireDate');
    const category = selectedOption.attr('data-category');

    $('#departure_name').val(name);
    $('#departure_hireDate').val(hireDate);
    $('#departure_category').val(category);
}
let absence = () => {
    const selectedOption = $("#personal_absence_personal :selected");
    const name = selectedOption.attr('data-name');
    const hireDate = selectedOption.attr('data-hireDate');
    const category = selectedOption.attr('data-category');

    $('#personal_absence_name').val(name);
    $('#personal_absence_hireDate').val(hireDate);
    $('#personal_absence_category').val(category);
}

$('body').on('change', '#charge_personal, #account_personal, #departure_personal, #conge_personal, #personal_heure_sup_personal, #personal_absence_personal', function () {
    if ($(this).attr('id') === 'charge_personal') {
        chargePeople();
    } else if ($(this).attr('id') === 'account_personal') {
        accountPersonal();
    } else if ($(this).attr('id') === 'departure_personal') {
        depart();
    } else if ($(this).attr('id') === 'conge_personal') {
        conge();
    } else if ($(this).attr('id') === 'personal_heure_sup_personal') {
        heureSupp();
    } else if ($(this).attr('id') === 'personal_absence_personal') {
        absence();
    }
});

chargePeople();
accountPersonal();
depart();
conge();
heureSupp();
absence();
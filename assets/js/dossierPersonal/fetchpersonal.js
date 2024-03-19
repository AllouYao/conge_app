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
    const dernierRetour = selectedOption.attr('data-dernier-retour');
    const remaining = selectedOption.attr('data-remaining');

    $('#conge_name').val(name);
    $('#conge_hireDate').val(hireDate);
    $('#conge_category').val(category);
    $('#conge_dernierRetour').val(dernierRetour);
    $('#conge_remaining').val(remaining);
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
let assurance = () => {
    const selectedOption = $("#assurance_personal_Personal :selected");
    const name = selectedOption.attr('data-name');
    const hireDate = selectedOption.attr('data-hireDate');
    const category = selectedOption.attr('data-category');

    $('#assurance_personal_name').val(name);
    $('#assurance_personal_hireDate').val(hireDate);
    $('#assurance_personal_category').val(category);
}
let operation = () => {
    const selectedOption = $("#operation_personal :selected");
    const name = selectedOption.attr('data-name');
    const hireDate = selectedOption.attr('data-hireDate');
    const category = selectedOption.attr('data-category');

    $('#operation_name').val(name);
    $('#operation_hireDate').val(hireDate);
    $('#operation_category').val(category);
}

$('body').on('change',
    '#charge_personal, #account_personal, #departure_personal, #conge_personal, #personal_heure_sup_personal, #personal_absence_personal, #assurance_personal_Personal, #operation_personal', function () {
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
        } else if ($(this).attr('id') === 'assurance_personal_Personal') {
            assurance();
        } else if ($(this).attr('id') === 'operation_personal') {
            operation();
        }
    });

chargePeople();
accountPersonal();
depart();
conge();
heureSupp();
absence();
assurance();
operation();
$(document).on('change', '#conge_dateDepart', function() {
    var typeConge = $("input[name='conge[typeConge]']:checked").val(); // Obtenez la valeur du type de congé sélectionné
    if (typeConge == "Effectif") {
        var dateDepart = new Date($(this).val()); 
        dateDepart.setDate(dateDepart.getDate() + 30); 
        var dateRetour = formatDate(dateDepart); 
        $('#conge_dateRetour').val(dateRetour); 
    } else if (typeConge == "Partiel") {
        var dateDepart = new Date($(this).val()); 
        dateDepart.setDate(dateDepart.getDate() + 14); 
        var dateRetour = formatDate(dateDepart); 
        $('#conge_dateRetour').val(dateRetour); 
    }
});
$(document).on('change', '#conge_typeConge_0', function() {
    var typeConge = $(this).val();
    if (typeConge == "Effectif") {
        var dateDepart = new Date($('#conge_dateDepart').val()); 
        dateDepart.setDate(dateDepart.getDate() + 30); 
        var dateRetour = formatDate(dateDepart); 
        $('#conge_dateRetour').val(dateRetour); 
    } else if (typeConge == "Partiel") {
        var dateDepart = new Date($('#conge_dateDepart').val()); 
        dateDepart.setDate(dateDepart.getDate() + 14); 
        var dateRetour = formatDate(dateDepart); 
        $('#conge_dateRetour').val(dateRetour); 
    }
});

$(document).on('change', '#conge_typeConge_1', function() {
    var typeConge = $(this).val();
    if (typeConge == "Effectif") {
        var dateDepart = new Date($('#conge_dateDepart').val()); 
        dateDepart.setDate(dateDepart.getDate() + 30); 
        var dateRetour = formatDate(dateDepart); 
        $('#conge_dateRetour').val(dateRetour); 
    } else if (typeConge == "Partiel") {
        var dateDepart = new Date($('#conge_dateDepart').val()); 
        dateDepart.setDate(dateDepart.getDate() + 14); 
        var dateRetour = formatDate(dateDepart); 
        $('#conge_dateRetour').val(dateRetour); 
    }
});

function formatDate(date) {
    var year = date.getFullYear();
    var month = ('0' + (date.getMonth() + 1)).slice(-2);
    var day = ('0' + date.getDate()).slice(-2);
    return year + '-' + month + '-' + day;
}

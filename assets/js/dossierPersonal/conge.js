$(document).on('change', '#conge_dateDepart', function() {
    var dateDepart = new Date($(this).val()); 
    var totalDays = $('#conge_days').val() ?  $('#conge_days').val(): 0;
    dateDepart.setDate(dateDepart.getDate() + totalDays); 
    var dateRetour = formatDate(dateDepart); 
    $('#conge_dateRetour').val(dateRetour); 
    $('#conge_dateReprise').val(dateRetour); 


    var dateDepart = new Date($('#conge_dateDepart').val()); 
    var dateRetour = new Date($('#conge_dateRetour').val()); 
    var totalDay = getFormatDays(dateRetour,dateDepart); 
    $('#conge_days').val(0); 
    $('#conge_days').val(totalDay); 
    setDayReste()

});



$(document).on('change', '#conge_dateRetour', function() {
    var dateDepart = new Date($('#conge_dateDepart').val()); 
    var dateRetour = new Date($('#conge_dateRetour').val()); 
    var totalDay = getFormatDays(dateRetour,dateDepart); 
    $('#conge_days').val(totalDay); 
    $('#conge_dateReprise').val($(this).val()); 

    setDayReste()

    
 });
$(document).on('change', '#conge_personal', function() {

    setDayReste()

});

function setDayReste(){
    var conge_remaining =  $('#conge_remaining').val()
    var jourPris =  $('#conge_days').val()

    var resteJour = conge_remaining - jourPris
    $('#conge_congeReste').val(resteJour)
}

$(document).ready(function() {
})

function formatDate(date) {
    var year = date.getFullYear();
    var month = ('0' + (date.getMonth() + 1)).slice(-2);
    var day = ('0' + date.getDate()).slice(-2);
    return year + '-' + month + '-' + day;
}

function getFormatDays(date1,date2) {
    var time1 = date1.getTime();
    var time2 = date2.getTime();
    var diffInMilliseconds = time1 - time2;
    // Conversion de la différence en jours
    var msInDay = 24 * 60 * 60 * 1000;
    var diffInDays = diffInMilliseconds / msInDay;
    // Arrondir la différence en jours
    diffInDays = Math.floor(diffInDays);
    return diffInDays;
}

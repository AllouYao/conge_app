

$(document).on('click', '.toggle-enable', function() {
    const personalId = $(this).val();
    $('#personalEnableInput').val(personalId);
    $('#personalEnable').submit(); 
});
$(document).on('click', '.toggle-disable', function() {
    const personalId = $(this).val();
    $('#personalDisableInput').val(personalId);
    $('#personalDisable').submit();
}); 

$(document).on('click', '.toggle-disable-all ', function() {
    const personalId = $(this).val();
    $('#disableAllInput').val(personalId);
    $('#disableALL').submit();
}); 
$(document).on('click', '#toggleAllPersonals', function() {
    $('#toggleAllInput').val($(this).val());
    $('#toggleAll').submit();
}); 

$(document).ready(function(){
    //alert()
});
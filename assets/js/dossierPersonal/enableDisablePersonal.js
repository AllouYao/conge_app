

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
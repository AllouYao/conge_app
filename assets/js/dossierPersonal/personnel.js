let marriedShow = () => {
    const $marie = $("#personal_etatCivil_2");

    if (!$marie.is(":checked")) {
        $("#family_container").hide();
        $("#personal_conjoint").val('');
        $("#personal_conjoint").removeAttr("required");
        $("#personal_numCertificat").val('');
        $("#personal_numCertificat").removeAttr("required");
        $("#personal_numExtraitActe").val('');
        $("#personal_numExtraitActe").removeAttr("required");
        $("#personal_isCmu").val('');
        $("#personal_isCmu").removeAttr("required");
        $("#personal_numCmu").val('');
        $("#personal_numCmu").removeAttr("required");
    } else {
        $("#family_container").show();
        $("#personal_conjoint").attr("required", "required");
        $("#personal_numCertificat").attr("required", "required");
        $("#personal_numExtraitActe").attr("required", "required");
        $("#personal_isCmu").attr("required", "required");
        $("#personal_numCmu").attr("required", "required");
    }
}


$(document).ready(function() {
    marriedShow();
    $("input[name='personal[etatCivil]']").on("change", function() {
        marriedShow();
    });
});
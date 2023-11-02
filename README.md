-- Ajout de phpFlasher

- Mise en place de l'enregistrement d'une catégorie.
- Mise en place de l'enregistrement d'une prime.
- Mise en place du dossier de personnel.


let getPrimeCoefficient = () => {
$('body').on('change', '.prime-salary', function () {
const parentId = $(this).parent().parent().attr('data-id');
const $prime = +$(`#${parentId}_prime option:selected`).attr('data-taux');
const coefficient = $(`#${parentId}_taux`);
const primes = $(`#${parentId}_prime`).val()
console.log(primes)
if (primes.length > 0) {
coefficient.val($prime);
const $smig = +$('#personal_salary_smig').val();
$horaire = $smig / 173.33;
$(`#${parentId}_smigHoraire`).val($horaire);
} else {
$(`#${parentId}_smigHoraire`).val(' ');
coefficient.val(' ');
}
calculateSmigHoraire(parentId)
})
}
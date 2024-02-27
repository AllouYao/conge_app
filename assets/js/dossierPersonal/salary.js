$(document).ready(function () {
    $('body')
        .on('change', '#assurance_personal_Personal', function () {
            const $form = $(this).closest('form');
            const personalEl = $('#assurance_personal_Personal');
            let data = {};
            data[personalEl.attr('name')] = personalEl.val()
            $.ajax({
                url: $form.attr('action'),
                type: $form.attr('method'),
                data,
                complete: function (html) {
                    console.log($(html.responseText).find('#assurance_personal_chargePeople'))
                    $(`#assurance_personal_chargePeople`).empty();
                    $(`#assurance_personal_chargePeople`).replaceWith($(html.responseText).find('#assurance_personal_chargePeople'));
                    $(`#assurance_personal_chargePeople`).select2();
                }
            });
        })

    /**
     $('body')
     .on('change', '#assurance_personal_retenuForfetaire', function () {
     const $form = $(this).closest('form');
     const personalEl = $('#assurance_personal_retenuForfetaire');
     let data = {};
     data[personalEl.attr('name')] = personalEl.val()
     $.ajax({
     url: $form.attr('action'),
     type: $form.attr('method'),
     data,
     complete: function (html) {
     $(`#charge-container`).empty();
     const element = $(html.responseText).find('#assurance_personal_chargePeople');
     console.log(element.find('input'))
     const div = document.createElement('div');
     div.innerHTML = `<label class="col-form-label col-md-3">Personne à charge</label>${element.html()}`;
     //const div = `<label class="custom-control-label col-md-2">Personne à charge</label><div class="col-md-4">${element.html()}</div>`;
     $('#charge-container').append(div)
     $(`#assurance_personal_chargePeople`).select2();
     }
     });
     }) **/
});

export const runInputmask = () => {
    $(function () {
        Inputmask('', {
            numericInput: true,
            rightAlign: false,
            autoUnmask: true,
            placeholder: '',
            removeMaskOnSubmit: true,
            groupSeparator: " ",
            greedy: false,
            digits: 0,
            alias: 'currency',
        }).mask('.separator');
    });
};

export const runDecimalInputmask = () => {
    $(function () {
        Inputmask('', {
            alias: 'currency',
            numericInput: true,
            groupSeparator: '',
            autoGroup: true,
            digits: 2,
            allowMinus: false,
            rightAlign: false,
            removeMaskOnSubmit: true,
            greedy: false,
        }).mask('.decimalInput')
    })
}
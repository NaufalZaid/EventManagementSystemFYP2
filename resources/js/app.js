import { Datepicker } from 'flowbite';

document.querySelectorAll('[data-weekdays-only]').forEach((dateInput) => {
    const datepicker = new Datepicker(dateInput, {
        autohide: true,
        format: 'yyyy-mm-dd',
        minDate: dateInput.dataset.minDate,
        orientation: 'bottom',
    });

    datepicker.getDatepickerInstance().setOptions({
        daysOfWeekDisabled: [0, 6],
    });
});

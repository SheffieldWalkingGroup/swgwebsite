(function(){
    var checkboxes, calendarTable, dateFields;
    
    
    var wireUp = function()
    {
        checkboxes = {
            weekends : document.getElementById('jform_basicavailability0'),
            weekdays : document.getElementById('jform_basicavailability1'),
    
            mondays    : document.getElementById('jform_weekdays0'),
            tuesdays   : document.getElementById('jform_weekdays1'),
            wednesdays : document.getElementById('jform_weekdays2'),
            thursdays  : document.getElementById('jform_weekdays3'),
            fridays    : document.getElementById('jform_weekdays4'),
        };
        
        checkboxes.mondays.dataset.master = checkboxes.weekdays.id;
        checkboxes.tuesdays.dataset.master = checkboxes.weekdays.id;
        checkboxes.wednesdays.dataset.master = checkboxes.weekdays.id;
        checkboxes.thursdays.dataset.master = checkboxes.weekdays.id;
        checkboxes.fridays.dataset.master = checkboxes.weekdays.id;
        
        dateFields = {
            weekends : [],
            
            mondays: [],
            tuesdays: [],
            wednesdays: [],
            thursdays: [],
            fridays: [],
 
            weekdays: [
                checkboxes.mondays,
                checkboxes.tuesdays,
                checkboxes.wednesdays,
                checkboxes.thursdays,
                checkboxes.fridays
            ]
        }
        
        calendarTable = document.getElementsByClassName('availabilitycalendar')[0];
            
        for (var i in checkboxes) {
            if (!checkboxes.hasOwnProperty(i)) {
                continue;
            }
            // i isn't scoped, so we catch its current value
            var checkbox = checkboxes[i];
            
            checkbox.addEventListener('click', function(e) { 
                onChangeCheckbox(this.value, this.checked); 
                
            });
        }
        
        syncHiddenFieldsToCheckboxes();
        updateDayCheckbox(dateFields.weekends, checkboxes.weekends);
        updateDayCheckbox(dateFields.mondays, checkboxes.mondays);
        updateDayCheckbox(dateFields.tuesdays, checkboxes.tuesdays);
        updateDayCheckbox(dateFields.wednesdays, checkboxes.wednesdays);
        updateDayCheckbox(dateFields.thursdays, checkboxes.thursdays);
        updateDayCheckbox(dateFields.fridays, checkboxes.fridays);
        updateDayCheckbox(dateFields.weekdays, checkboxes.weekdays);
        
        // If the weekdays checkbox is off, set all the weekday checkboxes to checked
        // So if we tick the weekdays checkbox it enables all days
        if (!checkboxes.weekdays.checked) {
            checkboxes.mondays.checked = checkboxes.tuesdays.checked = checkboxes.wednesdays.checked = checkboxes.thursdays.checked = checkboxes.fridays.checked = true;
        }
        
    }
    
    function syncHiddenFieldsToCheckboxes()
    {
        var rows = calendarTable.getElementsByTagName('tr');
        for (var i in rows) {
            if (!rows.hasOwnProperty(i)) {
                continue;
            }
            
            var inputEls = rows[i].getElementsByTagName('input');
            for (var j=0; j<inputEls.length; j++) {
                var input = inputEls[j];
                
                if (input.type != 'hidden')
                    continue;
                
                var checkbox = document.getElementById(input.id.substring(0, input.id.indexOf('_real')));
                
                switch (input.value) {
                    case "0":
                        checkbox.checked = checkbox.readOnly = checkbox.indeterminate = false;
                        break;
                    case "1":
                        checkbox.readOnly = checkbox.indeterminate = true;
                        checkbox.checked = false;
                        break;
                    case "2":
                        checkbox.checked = true;
                        checkbox.indeterminate = checkbox.readOnly = false;
                        break;
                }
                
                switch (input.dataset.dow) {
                    case "1":
                        dateFields.mondays.push(input);
                        break;
                    case "2":
                        dateFields.tuesdays.push(input);
                        break;
                    case "3":
                        dateFields.wednesdays.push(input);
                        break;
                    case "4":
                        dateFields.thursdays.push(input);
                        break;
                    case "5":
                        dateFields.fridays.push(input);
                        break;
                    default:
                        dateFields.weekends.push(input);
                        break;
                }
            }
        }
    }

    /**
     * Update the checkboxes that indicate the leader is available every (Monday) (for example)
     * based on which dates have been set in the calendar
     * Will be checked if all relevant days are checked, unckecked if none are, and indeterminate otherwise
     * This doesn't check if matching input fields and checkbox are passed in
     * 
     * @param {HTMLInputElement[]} fields The hidden input fields in the calendar
     * @param {HTMLInputElement} checkbox The checkbox to update
     */
    function updateDayCheckbox(fields, checkbox)
    {
        var anyChecked = false;
        var allChecked = true;
        
        for (var i in fields) {
            if (!fields.hasOwnProperty(i)) {
                continue;
            }
            
            if ((fields[i].type == 'checkbox' && fields[i].checked) || fields[i].value > 0) {
                anyChecked = true;
            } else {
                allChecked = false;
            }
        }
        
        if (allChecked) {
            checkbox.checked = true;
            checkbox.disabled = false;
        } else if (anyChecked) {
            checkbox.indeterminate = true;
            checkbox.disabled = false;
        } else {
            checkbox.checked = false;
        }
        
        // If this is a weekday, update the weekdays checkbox too
        if (checkbox.dataset.master) {
             // TODO
        }
    }
    
    function onChangeCheckbox(type, value)
    {
        switch(type) {
            case 'weekends':
                setDayValue(6, value);
                setDayValue(7, value);
                setBankHolidays(value);
                break;
            case 'weekdays':
                var weekdays = document.getElements('#jform_weekdays input');
                for (var i=0; i<weekdays.length; i++) {
                    weekdays[i].disabled = (!value);
                    // Set this day to enabled if weekdays AND this day are enabled
                    onChangeCheckbox(weekdays[i].value, (weekdays[i].checked && value));
                }
                // TODO: Enable individual day checkboxes (and set values)
                // TODO: Set weekends/weekdays indeterminate if not all enabled
                break;
            case 'mondays':
                setDayValue(1, value);
                break;
            case 'tuesdays':
                setDayValue(2, value);
                break;
            case 'wednesdays':
                setDayValue(3, value);
                break;
            case 'thursdays':
                setDayValue(4, value);
                break;
            case 'fridays':
                setDayValue(5, value);
                break;
        }
    }
    
    function setDayValue(dayOfWeek, enabled)
    {
        var rows = calendarTable.getElementsByTagName('tr');
        for (var i in rows) {
            if (!rows.hasOwnProperty(i)) {
                continue;
            }
            
            var inputEls = rows[i].getElementsByTagName('input');
            for (var j=0; j<inputEls.length; j++) {
                var input = inputEls[j];
                
                if (input.dataset.dow == dayOfWeek && input.dataset.bankholiday == "") {
                    setInputCellValue(input, enabled);
                }
            }
        }
    }
    
    /**
     * Marks a checkbox or hidden field as enabled or disabled
     */
    function setInputCellValue(input, enabled)
    {
        if (input.type == 'checkbox') {
            // If it's already set to a preferred day, don't reduce it to normal
            if (enabled && !input.checked && !input.indeterminate) {
                input.readOnly = true;
                input.indeterminate = true;
            } else if (!enabled) {
                input.readOnly = false;
                input.indeterminate = false;
                input.checked = false;
            }
        } else if (input.type == 'hidden') {
            if (enabled) {
                input.value = Math.max(1, input.value);
            } else {
                input.value = 0;
            }
        }
    }
    
    function setBankHolidays(enabled)
    {
        var inputEls = calendarTable.getElementsByTagName('input');
        for (var i=0; i<inputEls.length; i++) {
            var input = inputEls[i];
            if (input.dataset.bankholiday != "") {
                setInputCellValue(input, enabled);
            }
        }
    }
    
    if (document.readyState == 'loading') {
        window.addEventListener('DOMContentLoaded', function() {
            wireUp();
        });
    } else {
        wireUp();
    }
}());

/* TODO: Move inside closure */
/**
 * Handle tri-state checkbox
 * 
 * Checked = Best days, value = 2
 * Indeterminate = Can lead, value = 1
 * Unckecked = Can't lead, value = 0
 * 
 * Order:
 * Off -> indeterminate -> on -> off...
 */
function triState(cb) {
    var real = document.getElementById(cb.id + "_real");
    if (real.value == 2) {
        // Go to off
        cb.checked = cb.readOnly = false;
        real.value = 0;
    } else if (real.value == 1) {
        // Go to fully on
        cb.checked = true;
        cb.readOnly = false;
        real.value = 2;
    } else {
        // Go to half on
        cb.readOnly = cb.indeterminate = true;
        cb.checked = false;
        real.value = 1;
    }
}

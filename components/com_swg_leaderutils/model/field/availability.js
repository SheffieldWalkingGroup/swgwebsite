(function(){
    var checkboxes, calendarTable;
    
    
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
        
        onChangeCheckbox(checkboxes.weekends.value, checkboxes.weekends.checked);
        onChangeCheckbox(checkboxes.weekdays.value, checkboxes.weekdays.checked);
    }
    
    function onChangeCheckbox(type, value)
    {
        switch(type) {
            case 'weekends':
                setDayValue(6, value);
                setDayValue(7, value);
                break;
            case 'weekdays':
                var weekdays = document.getElements('#weekdays input');
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
    
    function setWeekendsEnabled(enabled)
    {
        setDayValue(6, enabled);
        setDayValue(7, enabled);
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
                
                if (input.dataset.dow == dayOfWeek) {
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

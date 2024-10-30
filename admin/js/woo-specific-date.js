
var Burd_Specific_Date = {

    add : function () {

        var length = jQuery(".select_specific_delivery").length + 1;

        var byWeek = '<optgroup label="By week days">' +
            '<option value="-4">Every monday</option>' +
            '<option value="-3">Every tuesday</option>' +
            '<option value="-2">Every wednesday</option>' +
            '<option value="-1">Every thursday</option>' +
            '<option value="0">Every friday</option>' +
            '</optgroup>';

        var byDate = '<optgroup label="By Dates">';

        for(var i = 1; 31 >= i; i++) {
            byDate+= '<option value="' + i + '">Burd will deliver ' + i + 'th of the month</option>';
        }

        byDate+= '</optgroup>';

        jQuery("#burd_specific_delivery_dates").prepend('<div id="specific_element_' + length + '">' +
            '<a href="javascript:void(0)" onclick="jQuery(\'#specific_element_' + length + '\').remove();">Remove</a> -' +
            ' <select class="select_specific_delivery" name="W_Burd_SpecificDeliveryDate[]" style="padding: 7px;">' + byWeek + ' ' + byDate + '' +
            '</select><br><br></div>');
    }
    
}
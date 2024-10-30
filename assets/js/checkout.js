/**
 * Burd check-out JS handler.
 */

var Burd_Checkout = {

    switchBetweenFlexOrTodayDelivery: function () {

        var flexElement = jQuery("#burd_flex_delivery_date");
        var futureDelivery = jQuery("#burd_future_delivery");


        if(flexElement.css('display') == 'none') {

            flexElement.show();
            futureDelivery.val(1);

        } else {
            flexElement.hide();
            futureDelivery.val(0);
        }

    }

}
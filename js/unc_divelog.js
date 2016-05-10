function datepicker_available(date, divecount) {
    off = date.getTimezoneOffset();
    // adjust for timezone
    off_inv = off * -1;
    date.addMinutes(off_inv);
    iso = date.toISOString();
    ymd = iso.substring(0, 10);
    if (jQuery.inArray(ymd, availableDates) !== -1) {
        return [true, formatCurrentDate(ymd), ymd + " has dives"];
    } else {
        return [false, "dateunavailable", "No images on " + ymd];
    }
}

function divepicker_select(dive_id, inst) {
    jQuery.ajax({
        url: ajaxurl,
        method: 'GET',
        dataType: 'text',
        data: {action: 'uncd_divelog_datepicker', date: dive_id},
        complete: function (response) {
            jQuery('#dives').html(response.responseText);
        },
        error: function () {

        }
    });
}

function datepicker_ready(defaultdate) {
    jQuery('#datepicker').datepicker({
        dateFormat: 'yy-mm-dd',
        defaultDate: defaultdate,
        beforeShowDay: datepicker_available,
        onSelect: divepicker_select
    });
}

function divelist_change() {
    var divelist_value = jQuery('#divepicker').val();
    divepicker_select(divelist_value);
}

function uncd_divelog_generic_ajax(action, target_div, confirmation_message) {
    jQuery('#' + target_div).html('');
    if (confirmation_message) {
        var c = confirm(confirmation_message);
    }
    if (c) {
        jQuery.ajax({
            url: ajaxurl,
            method: 'GET',
            dataType: 'text',
            data: {action: action},
            complete: function (response) {
                jQuery('#' + target_div).html(response.responseText);
            },
            error: function () {

            }
        });
    } else {
        jQuery('#' + target_div).html('Action cancelled!');
    }
}

// this parses the current iterated date and checks if it's the current displayed
function formatCurrentDate(dateYmd) {
    var query = window.location.search.substring(1);
    if (query.search(dateYmd) > 0) {
        return "dateavailable dateShown";
    } else {
        return "dateavailable";
    }
}

Date.prototype.addMinutes= function(m){
    this.setMinutes(this.getMinutes()+m);
    return this;
};

function MarkerWithLabelAndHover(marker){
    if (marker.get('hoverContent')){
        marker.set('defaultContent',marker.get('labelContent'))
        var fx=function(e,m){
            var r=e.relatedTarget;
            if(!r){
                return true;
            }
            while(r.parentNode){
                if(r.className==m.labelClass){
                    return false;
                }
                r=r.parentNode;
            }
            return true;
        }
        marker.set('defaultContent',marker.get('labelContent'))
        google.maps.event.addListener(marker,'mouseout',function(e){
            var that=this;
            if(fx(e,this)){
                this.set('labelContent', this.get('defaultContent'));
                this.set('labelClass', this.get('defaultClass'));
            }
        });
        google.maps.event.addListener(marker,'mouseover',function(e){
            var that=this;
            if(fx(e,this)){
                this.set('labelContent', this.get('hoverContent'));
                this.set('labelClass', this.get('hoverClass'));
            }
        });
    }
    return marker;
}
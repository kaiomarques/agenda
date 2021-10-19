/**
 * Created by Silver on 18/03/2016.
 */
function printMaskCnpj(data) {
    return data.substring(0,2)+'.'+data.substring(2,5)+'.'+data.substring(5,8)+'/'+data.substring(8,12)+'-'+data.substring(12,14);
}

function mascararDate(data, saida){
	var date = new Date(data);
	if (data == '0000-00-00 00:00:00') {
		return '00/00/0000 00:00:00';
	}
    year = date.getFullYear(),
    month = (date.getMonth() + 1).toString(),
    formatedMonth = (month.length === 1) ? ("0" + month) : month,
    day = date.getDate().toString(),
    formatedDay = (day.length === 1) ? ("0" + day) : day,
    hour = date.getHours().toString(),
    formatedHour = (hour.length === 1) ? ("0" + hour) : hour,
    minute = date.getMinutes().toString(),
    formatedMinute = (minute.length === 1) ? ("0" + minute) : minute,
    second = date.getSeconds().toString(),
    formatedSecond = (second.length === 1) ? ("0" + second) : second;
    return formatedDay + "/" + formatedMonth + "/" + year + " " + formatedHour + ':' + formatedMinute + ':' + formatedSecond;
}

$(document).ready(function () {

    $(window).resize(function() {
        var scre = $("body").width();
        if ( scre >= 992 ) {
            $(".navbar-nav").removeClass("slide-in");
        }
    });
});
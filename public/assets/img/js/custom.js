/**
 * Created by Silver on 18/03/2016.
 */
function printMaskCnpj(data) {
    return data.substring(0,2)+'.'+data.substring(2,5)+'.'+data.substring(5,8)+'/'+data.substring(8,12)+'-'+data.substring(12,14);
}

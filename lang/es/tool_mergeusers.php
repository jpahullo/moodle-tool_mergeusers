<?php

/**
 * Define Spanish language strings
 * @author Jordi Pujol-Ahulló <jordi.pujol@urv.cat> http://www.sre.urv.cat
 * @package    tool_mergeusers
 * @link http://moodle.org/mod/forum/discuss.php?d=103425
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Fusión de cuentas de usuario';
$string['header'] = 'Fusión de dos cuentas de usuario en una';
$string['header_help'] =
'<p>Dado un usuario a ser eliminado y un usuario a mantener, esta herramienta fusiona/mueve
 los datos relativos del usario a ser eliminado sobre el usuario a manetener.
 Es importante saber que ambos usuarios deben existir previamente, y que no se eliminará ninguna cuenta
 de Moodle. El administrador de sistema deberá eliminarlo manualmente si es necesario.</p>
 <p>Este proceso usa funciones que dependen de la base de datos y puede ser que su funcionamiento
 no esté totalmente comprobado para vuestra base de datos.</p>
 <p><strong>Recuerda que esta acción es irreversible!</strong></p>';
$string['usermergingheader'] = '&laquo;{$a->username}&raquo; (user ID = {$a->id})';
$string['errorsameuser'] = 'Tratando de combinar el mismo usuario';
$string['mergeusers'] = 'Fusiona cuentas de usuario';
$string['merging'] = 'Fusionado';
$string['into'] = 'dentro';
$string['newuserid'] = 'ID de usuario a mantener';
$string['olduserid'] = 'ID de usuario a eliminar';
$string['mergeusers:view'] = 'Fusión de cuentas de usuario';
$string['tableok'] = 'Tabla {$a} : correctamente actualizada';
$string['tableko'] = 'Tabla {$a} : no se ha podido actualizar correctamente!';
$string['logok'] = 'Estas son las operaciones realizadas en la base de datos:';
$string['logko'] = 'Se han producido los siguientes errores:';
$string['logid'] = 'Para futuras referencias, estos datos aparecen en el registro con id {$a}.';
$string['dbok'] = 'Fusión realizada correctamente';
$string['dbko'] = 'Error en la fusión! <br/>Si vuestra base de datos soporta transacciones,
 no se han realizado cambios en ella.';
$string['tableskipped'] = 'Para guardar registros y por seguredad, no procesamos la tabla <strong>{$a}</strong>.
 <br />Para eliminar dichos registros, elimina la cuenta de usuario antigua una vez esta acción
 haya finalizado correctamente.';
$string['errordatabase'] = 'Error en la base de dades de tipus {$a}';
$string['invaliduser'] = 'Usuario inválido';
$string['cligathering:description'] = "Introduce pares de identificadores de usuario para fusionar el primero sobre el segundo.\n
El primero (fromid) perderá todos sus datos y se pasaran al segundo (toid) que incorporará los datos de ambos.";
$string['cligathering:stopping'] = 'Para finalizar, Ctrl+C o introduce un -1 tanto en el fromid o en el toid.';
$string['cligathering:fromid'] = 'Id de usuario origen (fromid):';
$string['cligathering:toid'] =   'Id de usuario destino  (toid):';
$string['viewlog'] = 'Ver registro de fusiones';
$string['loglist'] = 'Este es el listado de fusiones, indicando si se llevaron a cabo satisfactoriamente:';
$string['newuseridonlog'] = 'ID de usuario mantenido';
$string['olduseridonlog'] = 'ID de usuario eliminado';
$string['nologs'] = 'No hay registros de fusión de usuarios. Bien por ti!';
$string['wronglogid'] = 'No existe el registro que estás solicitando.';
$string['deleted'] = 'Usuario {$a} eliminado';

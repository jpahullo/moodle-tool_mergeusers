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
$string['description'] =
'<p>Dado un ID de usuario a ser eliminado y un ID a mantener, esta herramienta fusiona/mueve
 los datos relativos del usario a ser eliminado sobre el ID de usuario a manetener.
 Es importante saber que ambos IDs deben existir previamente, y que no se eliminará ninguna cuenta
 de Moodle. El administrador de sistema deberá eliminarlo manualmente si es necesario.</p>
 <p>Este proceso usa funciones que dependen de la base de dadtos y puede ser que su funcionamiento
 no esté totalmente comprobado para vuestra base de datos. <strong>Recuerda que esta acción es
 irreversible!</strong></p>';
$string['usermergingheader'] = '&laquo;{$a->username}&raquo; (user ID = {$a->id})';
$string['errorsameuser'] = 'Tratando de combinar el mismo usuario';
$string['mergeusers'] = 'Fusiona cuentas de usuario';
$string['merging'] = 'Fusionando';
$string['into'] = 'dentro';
$string['newuserid'] = 'ID de usuario a mantener';
$string['olduserid'] = 'ID de usuario a eliminar';
$string['mergeusers:view'] = 'Fusión de cuentas de usuario';
$string['tableok'] = 'Tabla {$a} : correctamente actualizada';
$string['tableko'] = 'Tabla {$a} : no se ha podido actualizar correctamente!';
$string['logok'] = '<p><strong>Estas son las operaciones realizadas en la base de datos</strong><br/>
 Por favor, guarde esta página para futuras referencias.</p>';
$string['logko'] = 'Se han producido los siguientes errores:';
$string['dbok'] = 'Fusión realizada correctamente';
$string['dbko'] = 'Error en la fusión! <br/>Si vuestra base de datos soporta transacciones,
 no se han realizado cambios en ella.';
$string['tableskipped'] = 'Para guardar registros y por seguredad, no procesamos la tabla <strong>{$a}</strong>.
 <br />Para eliminar dichos registros, elimina la cuenta de usuario antigua una vez esta acción
 haya finalizado correctamente.';
$string['errordatabase'] = 'Error en la base de dades de tipus {$a}';
$string['invaliduser'] = 'Usuario inválido';

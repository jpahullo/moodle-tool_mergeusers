<?php

/**
 * Define Spanish language strings
 * @author Jordi Pujol-Ahulló <jordi.pujol@urv.cat> http://www.sre.urv.cat
 * @package    tool
 * @subpackage mergeusers
 * @link http://moodle.org/mod/forum/discuss.php?d=103425
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package admin-tool-mergeusers
 * @version 2013102200
 */

$string['pluginname'] = 'Fusión de cuentas de usuario';
$string['description'] = '
    <h1>Fusión de dos cuentas de usuario en una.</h1>
    <p>Dado un ID de usuario a ser eliminado y un ID a mantener, esta herramienta fusiona/mueve
    los datos relativos del usario a ser eliminado sobre el ID de usuario a manetener.
    Es importante saber que ambos IDs deben existir previamente, y que no se eliminará ninguna cuenta
    de Moodle. El administrador de sistema deberá eliminarlo manualmente si es necesario.</p>
    <p>Este proceso usa funciones que dependen de la base de dadtos y puede ser que su funcionamiento
    no esté totalmente comprobado para vuestra base de datos. <strong>Recuerda que esta acción es
    irreversible!</strong></p>';
$string['errorsameuser'] = 'Tratando de combinar el mismo usuario';
$string['mergeusers'] = 'Fusiona cuentas de usuario';
$string['merging'] = 'Fusionando';
$string['into'] = 'dentro';
$string['newuserid'] = 'ID de usuario a mantener';
$string['olduserid'] = 'ID de usuario a eliminar';
$string['mergeusers:view'] = 'Fusión de cuentas de usuario';
$string['tableok'] = 'Tabla {$a} : correctamente actualizada';
$string['tableko'] = 'Tabla {$a} : no se ha podido actualizar correctamente!';
$string['dbqueries'] = '<h2>Estas son las operaciones realizadas en la base de datos</h2>
    <p style="color: #f00;">Por favor, guarde esta página para futuras referencias.</p>';
$string['dbok'] = '<h1 style="color:#0c0;">Fusión realizada correctamente</h1>';
$string['dbko'] = '<h1 style="color:#f00;">Error en la fusión!</h1>
    <p>Si vuestra base de datos soporta transacciones, no se han realizado cambios en ella.</p>';
$string['tableskipped'] = 'Para guardar registros y por seguredad, no procesamos la tabla <strong>{$a}</strong>.
    <br />Para eliminar dichos registros, elimina la cuenta de usuario antigua una vez esta acción
    haya finalizado correctamente.';
$string['errordatabase'] = 'Error en la base de dades de tipus {$a}';

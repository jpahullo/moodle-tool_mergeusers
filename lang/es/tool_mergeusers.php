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
$string['dbko_transactions'] = '<strong>Error en la fusión!</strong> <br/>Su base de datos
    soporta transacciones. Por tanto, <strong>no se ha realizado ningún cambio
    en su base de datos</strong>.';
$string['dbko_no_transactions'] = '<strong>Error en la fusión!</strong> <br/>Su base de datos
    no soporta transacciones. Por tanto, su base de datos <strong>ha sido modificada</strong>
    y ha podido quedar en un estado inconsistente. <br/>Revisa el registro de la fusión e
    informa de los errores a los desarrolladores del plugin y en breve se solucionará. <br/>
    Una vez actualice el plugin a la última versión que introducirá la solución
    correspondiente, repita la fusión de dichos usuarios y así se completará satisfactoriamente.';
$string['tableskipped'] = 'Para guardar registros y por seguredad, no procesamos la tabla <strong>{$a}</strong>.
 <br />Para eliminar dichos registros, elimina la cuenta de usuario antigua una vez esta acción
 haya finalizado correctamente.';
$string['errordatabase'] = 'Error: tipo de base de datos {$a} no soportada.';
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
$string['errortransactionsonly'] = 'Error: se requiren transacciones, y su base de datos {$a} no las soporta.
    Si lo necesita, puede configurar que las fusiones se hagan sin transacciones.
    Revise la configuración para que se ajuste a sus necesidades.';

//New strings

// Progress bar
$string['choose_users'] = 'Escoge los usuarios a fusionar';
$string['review_users'] = 'Confirma los usuarios a fusionar';
$string['results'] = 'Resultados de la fusión';

// Form Strings
$string['form_header'] = 'Busca los usuarios a fusionar';
$string['form_description'] = '<p>A continuación puedes buscar los usuarios a fusionar.
    También, si conoces el nombre de usuario, su id o su idnumber, puedes introducirlos
    en las opciones avanzadas.';
$string['searchuser'] = 'Buscar usuario por';
$string['searchuser_help'] = 'Introduce un nombre de usuario, nombre, apellido(s),
    email o id para listar usuarios potenciales. Para una búsqueda más ajustada,
    también puedes seleccionar el campo por el que deseas buscar.';
$string['mergeusersadvanced'] = '<strong>Entrada d\'ids</strong>';
$string['mergeusersadvanced_help'] = 'Si el campo de búsqueda está vacío,
    des de esta sección podrás seleccionar el usuario a mantener y eliminar
    en un solo paso, introduciendo los valores adecuados y el tipo de identificador.<br /><br />
    Después haz clic sobre el botón de búsqueda para verificar/confirmar los usuarios a fusionar.';
$string['mergeusers_confirm'] = 'La fusión se iniciará después de confirmar
    la fusión de los usuaris. <br /><strong>Esta acción es irreversible!</strong><br />
    Estás seguro de fusionar los usuaris?';
$string['clear_selection'] = 'Deselecciona los usuarios a fusionar';

// Merge users select table
$string['olduser'] = 'Usuario a eliminar';
$string['newuser'] = 'Usuario a mantener';
$string['saveselection_submit'] = 'Guarda selección';
$string['userselecttable_legend'] = '<b>Selecciona usarios a fusionar</b>';

// Merge users review table
$string['userreviewtable_legend'] = '<b>Usuarios a fusionar</b>';

// Error string
$string['error_return'] = 'Vuelve al formulario de búsqueda';
$string['no_saveselection'] = 'No has seleccionado ningún usuario.';
$string['invalid_option'] = 'Opción inválida';

// Settings page
$string['suspenduser_setting'] = 'Suspender usuario a eliminar';
$string['suspenduser_setting_desc'] = 'Si se activa, el usuario a eliminar
    se suspenderá automáticamente si la fusión termina satisfactoriamente,
    lo que evitará que se autentique en Moodle (recomendado).
    Si se desactiva, el usuario a eliminar permanecerá activo.
    En ambos casos, el usuario a eliminar no dispondrá de sus datos ni de su
    actividad de Moodle.';
$string['transactions_setting'] = 'Sólo transacciones';
$string['transactions_setting_desc'] = 'Si se activa, la fusión de usuarios no
    se realizará si la base de datos NO soporta transacciones (recomendado).
    Con esta opción activa, te aseguras que la base de datos permanecerá
    siempre consistente, incluso si la fusión termina con errores.<br />
    Si se desactiva, siempre realizarás la fusión de usuarios.
    En caso de errores, el registro de la fusión te mostrará cuál fue el problema.
    Si informas de este error a los desarrolladores de este plugin,
    tendrás la solución en breve.<br />
    Ten en cuenta que este plugin gestiona correctamente todas las
    tablas de la base de datos de Moodle, y también de algun plugin de
    terceras partes. Por tanto, si sólo tienes una instalación Moodle estándard,
    puedes ejecutar este plugin tranquilamente tanto con esta opción activada
    com desactivada.';
$string['transactions_supported'] = 'Para su información, su base
    de datos <strong>soporta transacciones</strong>.';
$string['transactions_not_supported'] = 'Para su información, su base
    de datos <strong>no soporta transacciones</strong>.';

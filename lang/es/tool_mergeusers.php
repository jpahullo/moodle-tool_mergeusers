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

// quiz attempts strings
$string['quizattemptsaction'] = 'Cómo resolver los intentos de un cuestionario';
$string['quizattemptsaction_desc'] = 'En la fusión de intentos de un cuestionario pueden suceder una de estas tres
    situaciones:
    <ol>
    <li>El usuario antiguo fue el único que intentó el cuestionario. Se mueven todos al usuario nuevo como si éste
    los hubiera realizado.</li>
    <li>El usuario nuevo es el único que intentó el cuestionario. No se hace nada, ya que está todo correcto.</li>
    <li>Ambos usuarios realizaron intentos para el mismo cuestionario. <strong>En este caso es cuando se aplica la
    acción que estás escogiendo en esta configuración</strong>. Las acciones pueden ser las siguientes:
        <ul>
        <li><strong>{$a->renumber}</strong>. Se añaden los intentos del usuario antiguo al usuario nuevo y se
        reenumeran todos según el tiempo de inicio de cada intento.</li>
        <li><strong>{$a->delete_fromid}</strong>. Se dejan sólo los intentos realizados por el usuario nuevo y se
        eliminan los del usuario antiguo. Por tanto, se hacen prevaler los últimos intentos.</li>
        <li><strong>{$a->delete_toid}</strong>. Se dejan sólo los intentos del usuario antiguo y se
        eliminan los del usuario nuevo. Aquí se hacen prevaler los primeros.</li>
        <li><strong>{$a->remain}</strong> (por defecto). Los intentos se mantienen relacionados a los usuarios
        que los generó, sin fusionarlos ni eliminarlos. Es la opción más segura si no se conocen los efectos, pero
        puede generar diferentes notas para el cuestionario según se fusione el usuario A en B o de B en A.</li>
        </ul>
    </li>
    </ol>';
$string['qa_action_renumber'] = 'Une todos los intentos y reenuméralos';
$string['qa_action_delete_fromid'] = 'Mantiene los intentos del usuario nuevo';
$string['qa_action_delete_toid'] = 'Mantiene los intentos del usuario viejo';
$string['qa_action_remain'] = 'No hacer nada: ni se fusionan ni eliminan';
$string['qa_action_remain_log'] = 'Se mantienen intactos los datos de los usuarios en la tabla <strong>{$a}</strong.';
$string['qa_chosen_action'] = 'Opción activa para intentos de cuestionario: {$a}.';

$string['qa_grades'] = 'Calificaciones recalculadas para los cuestionarios: {$a}.';

// Settings page
$string['suspenduser'] = 'Suspender usuario a eliminar';
$string['suspenduser_desc'] = 'Si se activa, el usuario a eliminar
    se suspenderá automáticamente si la fusión termina satisfactoriamente,
    lo que evitará que se autentique en Moodle (recomendado).
    Si se desactiva, el usuario a eliminar permanecerá activo.
    En ambos casos, el usuario a eliminar no dispondrá de sus datos ni de su
    actividad de Moodle.';
$string['transactions'] = 'Sólo transacciones';
$string['transactions_desc'] = 'Si se activa, la fusión de usuarios no
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
$string['transactions_supported'] = 'Para su información, tu base
    de datos <strong>soporta transacciones</strong>.';
$string['transactions_not_supported'] = 'Para su información, tu base
    de datos <strong>no soporta transacciones</strong>.';
$string['tablemerger_settings'] = 'Configuración para la fusión de tablas';
$string['tablemerger_settings_desc'] = 'Este plugin implementa varios elementos
    para fusionar los registros de las tablas de base de datos, y así
    realizar la fusión de usuarios. Su configuración específica aparece a continuación.';
$string['cronsettings'] = 'Configuración para el cron';
$string['cronsettings_help'] = 'Puedes definir una herramienta para obtener
    el listado de usuarios a fusionar. Por defecto, este plugin
    provee la herramienta interactiva CLIGathering.<br>
    En cambio, puedes definir tu herramienta no interactiva, colocar
    el script CLI en el cron del servidor y así procesar la fusión de
    usuarios automáticamente.<br>
    Para ello, debes desarrollar tu classe que implemente la interfaz
    Gathering. Esta clase será básicament un iterador que, en cada
    iteración, devuelve un objeto con los atributos \'fromid\'
    y \'toid\', que identifican a los usuarios a fusionar mediante sus \'user.id\'.';
$string['cronsettings_desc'] = 'Si pones el script CLI en el cron del servidor
    y provees una herramienta Gathering no iteractiva, puedes realizar la
    fusión de usuarios automáticamente. Por defecto, CLIGathering es una
    herramienta interactiva y no sirve a tal efecto. Visita la ayuda para saber más.';
$string['gathering'] = 'Herramienta de listado';
$string['gathering_desc'] = 'La herramienta de listado Gathering es básicamente un iterador.
    A cada iteración este provee un objeto con los atributos \'fromid\' y \'toid\'
    que identifican los usuarios a fusionar.';
$string['exclude_tables_settings'] = 'Excluye tablas a fusionar';
$string['exclude_tables_settings_help'] = 'Cuando se excluye una tabla de base
    de datos del proceso de fusión de usuarios se prohibe a este plugin su
    procesamiento, con lo cual, las tablas seleccionadas se dejan intactas.
    Aunque parezca extraño, esto es necesario en ciertos casos.<br>
    Nuestra experiencia nos dice que
    las siguientes tablas de base de datos se habrían de excluir durante la fusión
    de usuarios y así, además, proveer el comportamiento por defecto a este
    plugin: my_pages, user_info_data, user_preferences, user_private_key. Ver
    README para más detalles técnicos. <br>
    De hecho, my_pages siempre se debería de excluir, ya que cuando un usuario
    dispone de más de un registro en esta tabla, hace que Mi Moodle deje de funcionar.';
$string['exclude_tables_settings_desc'] = 'Selecciona las tablas de base de datos
    que se tienen que excluir durante la fusión de usuarios.';
$string['excluded_tables'] = 'Tablas excluidas';
$string['excluded_tables_desc'] = 'Tablas excluidas de la fusión de usuarios.';
$string['tablesettings'] = 'Tablas y columnas relacionadas con user.id';
$string['tablesettings_help'] = 'Esta sección es muy importate, y tú como
    administrador debes tener mucho cuidado con ella.<br>
    A continuación tienes la posibilidad de definir los nombres de columna
    que están relacionados con la columna user.id. Y lo puedes hacer de dos modos.
    La primera manera es definiendo un listado común y <strong>genérico de nombres
    de columna</strong> que, si aparecen <strong>siempre y nada más estarán vinculados
    con la columna user.id</strong>, independientemente de la tabla donde se encuentre.
    La segunda manera es definiendo <strong>un listado de tablas con nombres de
    columna específicos y únicos ligados a user.id</strong>.<br><br>
    Teniendo todo esto configurado, este plugin te comprobará toda la base de
    datos de Moodle y fusionará dos usuarios considerando sólo:<ul>
    <li>Estas tablas con los nombres de columna específicos.</li>
    <li>La resta de tablas considerando los nombres de columna genéricos.</li>
    </ul>Por esto es importate que esta configuración refleje e incluya
    todo nombre de columna que esté relacionado con user.id.';
$string['specifiedtablesettingsoperation'] = 'Para definir los nombres de
    columna particulares para las tablas seleccionadas, <strong>tienes que
    visitar esta página de configuración dos veces</strong> y proceder como
    sigue:<ol>
    <li>Seleccionar las tablas con nombres de columna relacionades con user.id
    personalitzado y guardar la configuración.</li>
    <li>Revisitar esta página y rellenar el listado de columnas relacionadas
    con user.id para cada tabla seleccionada anteriormente. Finalmente, guarda
    de nuevo la configuración.</li>
    </ol>Esta configuración es más prioritaria que el listado genérico de
    nombres de columna.';
$string['user_related_columns_for_default_setting'] = 'Columnas genéricament relacionadas con user.id';
$string['user_related_columns_for_default_setting_desc'] = 'Todos los nombres
    de columna de tu base de datos Moodle aparecen en este listado. Selecciona
    aquellos que, <strong>en caso de aparecer en cualquier tabla siempre estarán
    relacionados con user.id</strong>.';
$string['tables_with_custom_user_related_columns'] = 'Tablas con nombres de
    columna específicos relacionados con user.id';
$string['tables_with_custom_user_related_columns_desc'] = 'Todas las tablas
    de tu base de datos Moodle aparecen en este listado.
    Selecciona aquellas tablas que dispongan de nombres de columna exclusivos
    relacionados con user.id. Deberían diferir de aquellos que aparecen
    en el listado genérico.';
$string['user_related_columns_for_table_setting_desc'] = 'Escoge todos los
    nombres de columna de esta table que esten relacionados con user.id.';
$string['unique_indexes_settings'] = 'Índices compuestos únicos';
$string['unique_indexes_settings_desc'] = 'Este es el listado de<strong>índices
    compuestos únicos</strong> de la base de datos de Moodle con alguna columna
    relacionada con user.id. Todos estos índices son procesados por este
    plugin cuando se fusionan dos usuarios. Como los índices compuestos únicos
    no permiten múltiples registros con los mismos valores en el índice,
    este plugin gestiona esta multiplicidad antes de actualizar la
    base de datos.<br><br>
    El listado contine los nombres de tabla, índices y las columnas que los
    definen. Las columnas resaltadas están relacionadas con user.id.';
$string['table'] = 'Tabla de base de datos';
$string['index'] = 'Índice';
$string['columns'] = 'Lista ordenada de columnas que forman el índice';
$string['nonunique_index_settings'] = 'Índices compuestos no únicos';
$string['nonunique_index_settings_help'] = 'Todos los índices que aparecen
    en esta sección son no únicos. Esto significa que por defecto, tu
    base de datos permite múltiples registros con los mismos valores por cada
    índice.<br><br>
    No obstante, hay casos en los que no tiene sentido mantener diferentes registros cuando,
    en nuestro caso, hacen referencia a la misma persona cuando se le
    fusionan dos usuarios de Moodle. Así pues, en esta sección podrás
    escoger los índices compuestos que serán procesados como si fuesen únicos.
    Por lo tanto, no se permitirá más de un registro con los mismos valores
    por índice, <strong>siempre sin modificar la estructura de tu
    base de datos</strong>.';
$string['nonunique_index_operation'] = 'A continuación puedes decidir qué índices
    compuestos no únicos deberían ser procesados como si fuesen únicos
    <strong>sin modificar la estructura de tu base de datos</strong>.
    Para ello debes seguir estos senzillos pasos:<ol>
    <li>Selecciona del listado aquellos índices no únicos a procesar por este
    plugin como si fuesen únicos.</li>
    <li>Guarda la configuración.</li>
    </ol>Los valores por defecto con <strong>sí</strong> definen el
    comportamiento por defecto de este plugin.<br><br>
    Los índices aparecen descritos indicándoles
    <strong>{nombre de la tabla} - {nombre del índice} : {columna1}, {columna2}[, ...]</strong>.
    Las columnas remarcadas referencian a valores de la columna user.id.';
$string['tables_with_adhoc_indexes_settings'] = 'Define índices compuestos a medida';
$string['tables_with_adhoc_indexes_settings_help'] = 'La estructura de base de
    datos actual puede no contener los índices compuestos necesarios para poder
    realizar una fusión de usuarios adecuada y con sentido.<br><br>
    Para dar solución a este problema, <strong>y sin modificar la estructura de
    tu base de datos</strong>, te permitimos que definas aquí índices compuestos
    a medida. Este plugin usa los índices para identificar datos duplicados.<br><br>
    Para definir índices compuestos a medida sólo debes seguir estos pasos:
    seleccionas las tablas del listado sobre las que necesitas definir índices,
    guardas la configuración, luego defines las columnas que formarán el índice
    dentro de cada tabla, y guardas de nuevo la configuración.
    Una columna de las seleccionadas debe estar relacionada con user.id.';
$string['tables_with_adhoc_indexes_settings_desc'] = 'Puedes definir índices
    compuestos a medida <strong>sin modificar la estructura de tu base de
    datos</strong> siguiendo los siguientes pasos:<ol>
    <li>Del listado de tablas, escoge aquellas sobre las que definir los
    índices a medida.</li>
    <li>Guarda la configuración.</li>
    <li>Para cada tabla seleccionada, define las columnas que formarán el índice.</li>
    <li>Guarda la configuración de nuevo.</li>
    </ol>';
$string['tables_with_adhoc_indexes'] = 'Tablas con índices a medida';
$string['tables_with_adhoc_indexes_desc'] = 'Define las tablas que deben tener
    índices compuestos a medida.';
$string['columns_for_adhoc_index_for_table_setting_desc'] = 'Define las columnas
    que formarán el nuevo índice compuesto a medida para esta tabla.';
$string['check_indexes_settings'] = 'Comprobación de índices';
$string['check_indexes_settings_desc'] = 'A continuación aparece el listado
    de índices completo, tanto los propios de tu base de datos, como los que
    has definido a medida anteriormente si así lo has necesitado. Todos ellos
    tienen como mínimo una columna relacionada con la user.id.
    El listado muestra: los nombres de las tablas, de los índices, el tipo de
    índice y las columnas del índice en orden de definición. Las columnas
    remarcadas están relacionadas con la user.id.
    <strong>Este plugin usa estos índices para realizar adecuadamente la fusión
    de los usuarios</strong>.<br><br>
    Si crees que falta algún índice, o bien una fusión de usuarios te ha mostrado
    un error, deberías revisar la configuración de arriba sobre <strong>las
    columnas relacionadas con la user.id</strong> en cualesquiera de sus formas.
    Después, deberías revisar de nuevo el listado de índices únicos y no únicos,
    por si allí aparece lo que necesitas, o bien definir tu propio índice a
    medida. Al final, deberías ver en este listado de abajo aquel índice que te
    hacía falta y que ahora te permite fusionar los usuarios con normalidad.<br><br>
    Ve con sumo cuidado cuando actualices la configuración.';
$string['noindexes'] = 'No hemos encontrado índices compuestos con campos
    relacionados a usuarios moodle. Es muy extraño. Deberías verificar
    seriosamentE la estructura de tu base de datos.';
$string['uniqueness'] = 'Unicidad';
$string['uniqueness0'] = 'No único';
$string['uniqueness1'] = 'Único';
$string['uniqueness2'] = 'A medida';

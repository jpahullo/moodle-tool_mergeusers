<?php

/**
 * Define Catalan language strings
 * @author Jordi Pujol-Ahulló, SREd, Universitat Rovira i Virgili
 * @package    tool_mergeusers
 * @link http://moodle.org/mod/forum/discuss.php?d=103425
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Fusió de comptes d\'usuari';
$string['header'] = 'Fusió de dos comptes d\'usuari en un de sol';
$string['header_help'] =
 '<p>Donat un usuari a ser eliminat i un usuari a mantenir, aquesta eina fusiona/mou
 les dades relatives de l\'usuari a ser eliminat sobre l\'usuari a mantenir.
 És important saber que tots dos usuaris existeixin prèviament, i que cap compte s\'eliminarà
 de Moodle. És tasca de l\'administrador de sistema d\'eliminar-lo manualment si s\'escau.</p>
 <p><strong>Recorda que aquesta acció és irreversible!</strong></p>';
$string['usermergingheader'] = '&laquo;{$a->username}&raquo; (user ID = {$a->id})';
$string['errorsameuser'] = 'Tractant de combinar el mateix usuari';
$string['mergeusers'] = 'Fusiona comptes d\'usuari';
$string['merging'] = 'Fusionat';
$string['into'] = 'dins';
$string['newuserid'] = 'ID d\'usuari a mantenir';
$string['olduserid'] = 'ID d\'usuari a eliminar';
$string['mergeusers:view'] = 'Fusió de comptes d\'usuari';
$string['tableok'] = 'Taula {$a} : correctament actualitzada';
$string['tableko'] = 'Taula {$a} : no s\'ha pogut actualitzar correctament!';
$string['logok'] = 'Aquestes són les operacions realitzades a la base de dades:';
$string['logko'] = 'S\'han produït els següents errors:';
$string['logid'] = 'Per futures referències, aquestes dades apareixen en el registre amb id {$a}.';
$string['dbok'] = 'Fusió satisfactòria';
$string['dbko_transactions'] = '<strong>Fusió fallida!</strong> <br/>La seva base de dades suporta
    transaccions. Per tant, <strong>no s\'ha realitzat cap canvi a la seva base de dades</strong>.';
$string['dbko_no_transactions'] = '<strong>Fusió fallida!</strong> <br/>La seva base de dades no suporta
    transaccions. Per tant, la seva base de dades <strong>ha estat modificada</strong> i ha pogut quedar
    en un estat inconsistent. <br/>Revisa el registre de la fusió i informa dels errors als
    desenvolupadors del plugin i en breu rebrà la solució. <br/>
    Una vegada actualitzi el plugin a la darrera versió que inclourà la solució corresponent,
    repeteixi la fusió dels usuaris i així la completarà satisfactòriament.';
$string['tableskipped'] = 'Per guardar registres i seguretat, no processem la taula <strong>{$a}</strong>.
 <br />Per eliminar aquestes entrades, elimina el compte d\'usuari antic una vegada aquesta acció
 hagi finalitzat correctament.';
$string['errordatabase'] = 'Error: tipus de base de dades {$a} no suportada.';
$string['invaliduser'] = 'Usuari invàlid';
$string['cligathering:description'] = "Introdueix parells d'identificadors d'usuari per fusionar el primer sobre el segon.
El primer (fromid) perdrà totes les seves dades i es passaran al segon (toid) que inclourà les dades d'ambdós.";
$string['cligathering:stopping'] = 'Per concloure, Ctrl+C o introdueix un -1 tant en el fromid o en el toid.';
$string['cligathering:fromid'] = 'Id d\'usuari font (fromid):';
$string['cligathering:toid'] =   'Id d\'usuari destí  (toid):';
$string['viewlog'] = 'Veure registre de fusions';
$string['loglist'] = 'Aquest és el llistat de fusions, indicant també si el resultat fou satisfactori:';
$string['newuseridonlog'] = 'ID d\'usuari mantingut';
$string['olduseridonlog'] = 'ID d\'usuari eliminat';
$string['nologs'] = 'No hi ha registres de fusions d\'usuari. Bé per tu!';
$string['wronglogid'] = 'No existeix el registre que estàs demanant.';
$string['deleted'] = 'Usuari {$a} eliminat';
$string['errortransactionsonly'] = 'Error: es requereixen transaccions, i la seva base de dades {$a} no les suporta.
    Si ho necessita, pot configurar que les fusions es realitzin sense transaccions.
    Revisi la configuració perquè s\'ajusti a les seves necessitats.';
$string['eventusermergedsuccess'] = 'Fusió correcta';
$string['eventusermergedfailure'] = 'Fusió incorrecta';

//New strings

// Progress bar
$string['choose_users'] = 'Escull els usuaris a fusionar';
$string['review_users'] = 'Confirma els usuaris a fusionar';
$string['results'] = 'Resultats de la fusió';

// Form Strings
$string['form_header'] = 'Cerca els usuaris a fusionar';
$string['form_description'] = '<p>A continuació pots cercar els usuaris a fusionar.
    També, si coneixes el nom d\'usuari, el seu id o el seu idnumber, pots introduir-ho
    anant a les opcions avançades.';
$string['searchuser'] = 'Cerca usuari per';
$string['searchuser_help'] = 'Introdueix un nom d\'usuari, nom, cognom(s),
    email o id per llistar usuaris potencials. Per una cerca més ajustada,
    també pots seleccionar el camp pel que desitges cercar.';
$string['mergeusersadvanced'] = '<strong>Entrada d\'ids</strong>';
$string['mergeusersadvanced_help'] = 'Si el camp de cerca és buit,
    en aquesta secció podràs seleccionar l\'usuari a mantenir i eliminar
    en un sol pas, introduint els valors adequadament i el tipus d\'identificadors.<br /><br />
    Després clica al botó de cerca per verificar/confirmar els usuaris a fusionar.';
$string['mergeusers_confirm'] = 'La fusió s\'iniciarà després de confirmar
    la fusió dels usuaris. <br /><strong>Aquesta acció és irreversible!</strong><br />
    Estàs segur de fusionar els usuaris?';
$string['clear_selection'] = 'Deselecciona els usuaris a fusionar';

// Merge users select table
$string['olduser'] = 'Usuari a eliminar';
$string['newuser'] = 'Usuari a mantenir';
$string['saveselection_submit'] = 'Guarda la selecció';
$string['userselecttable_legend'] = '<b>Selecciona usuaris a fusionar</b>';

// Merge users review table
$string['userreviewtable_legend'] = '<b>Usuaris a fusionar</b>';

// Error string
$string['error_return'] = 'Retorna al formulari de cerca';
$string['no_saveselection'] = 'No has seleccionat cap usuari.';
$string['invalid_option'] = 'Opció incorrecta';

// Settings page
$string['suspenduser_setting'] = 'Suspendre usuari a eliminar';
$string['suspenduser_setting_desc'] = 'Si s\'activa, l\'usuari a eliminar
    es suspendrà automàticament si la fusió conclou satisfactòriament,
    la qual cosa evitará que l\'usuari s\'autentiqui a Moodle (recomanat).
    Si es desactiva, l\'usuari a eliminar romandrà actiu. En ambdós casos,
    l\'usuari es quedarà sense les seves dades i la seva activitat de Moodle.';
$string['transactions_setting'] = 'Només transaccions';
$string['transactions_setting_desc'] = 'Si s\'activa, la fusió d\'usuaris no
    es realitzarà si la base de dades NO suporta transaccions (recomanat).
    Amb aquesta opció activa, t\'assegures que la base de dades romandrà
    sempre consistent, tot i que la fusió es conclogui amb errors. <br />
    Si es desactiva, sempre realitzaràs la fusió d\'usuaris.
    En cas d\'errors, el registre de la fusió et mostrarà quin és el problema.
    Si notifiques d\'aquest error als desenvolupadors d\'aquest plugin,
    tindràs la solució en breu.<br />
    Tingues en compte que aquest plugin gestiona correctament totes les
    taules de la base de dades de Moodle, i també d\'algun plugin de
    terceres parts. Per tant, si només tens una instal·lació Moodle estàndar,
    pots executar aquest plugin tranquilament tant amb aquesta opció activada
    com desactivada.';
$string['transactions_supported'] = 'Per la seva informació, la seva base
    de dades <strong>suporta transaccions</strong>.';
$string['transactions_not_supported'] = 'Per la seva informació, la seva base
    de dades <strong>no suporta transaccions</strong>.';
$string['excluded_exceptions'] = 'Excloure excepcions';
$string['excluded_exceptions_desc'] = 'Experiència en aquest àmbit ens suggereix
    que aquestes taules de base de dades s\'han d\'excloure del procés de fusió.
    Veure el README per més detalls.<br>
    Per tant, si vols aplicar el comportament per defecte, has de triar l\'opció
    \'{$a}\' per excloure-les del procés de fusió (recomanat).<br>
    Si ho prefereixes, pots seleccionar les taules que desitgis per incloure-les
    en el procés de fusió (no recomanat).';

// quiz attempts strings
$string['quizattemptsaction'] = 'Com resoldre els intents de qüestionari';
$string['quizattemptsaction_desc'] = 'En la fusió d\'intents de qüestionari hi poden haver tres
    situacions:
    <ol>
    <li>L\'usuari vell fou l\'únic que va intentar el qüestionari. Es mouen com si els
    hagués fet l\'usuari nou.</li>
    <li>L\'usuari nou és l\'únic que va intentar el qüestionari. No es fa res, doncs ja
    està tot correcte.</li>
    <li>Tots dos usuaris tenen intents en el mateix qüestionari. <strong>En aquest cas és quan
    s\'aplica l\'acció que estàs triant aquí</strong>. Poden ser les següents:
        <ul>
        <li><strong>{$a->renumber}</strong>. Afegeix els intents de l\'usuari vell a l\'usuari nou i es
        reenumeren segons la data d\'inici de cada intent.</li>
        <li><strong>{$a->delete_fromid}</strong>. Deixa només els intents fets per l\'usuari nou i elimina
        els de l\'usuari vell. Aquí es fan prevaldre els darrers intents.</li>
        <li><strong>{$a->delete_toid}</strong>. Deixa només els intents de l\'usuari vell i elimina
        els de l\'usuari nou. Aquí es fan prevaldre els primers intents.</li>
        <li><strong>{$a->remain}</strong> (per defecte). Els intents es mantenen relacionats als ususaris que els
        van generar, sense fusionar-los ni eliminar-los. És l\'opció més segura si no es coneixen els efectes, però pot
        provocar diferents notes al qüestionari, segons es fusioni de l\'usuari A en B, o de B en A.</li>
        </ul>
    </li>
    </ol>';
$string['qa_action_renumber'] = 'Ajunta tots els intents i reenumera\'ls';
$string['qa_action_delete_fromid'] = 'Manté els intents de l\'usuari nou';
$string['qa_action_delete_toid'] = 'Manté els intents de l\'usuari vell';
$string['qa_action_remain'] = 'Mantenir intents intactes, sense fusionar-los ni eliminar-los';
$string['qa_action_remain_log'] = 'Es mantenen intactes les dades dels usuaris a la taula <strong>{$a}</strong>.';
$string['qa_chosen_action'] = 'Opció activa per intents de qüestionari: {$a}.';

$string['qa_grades'] = 'Qualificacions recalculades pels qüestionaris: {$a}.';

$string['uniquekeynewidtomaintain'] = 'Mantenir dades usuari nou';
$string['uniquekeynewidtomaintain_desc'] = 'En casos de conflicte, com ara si la '
    . 'columna relativa a l\'usuari sigui índex únic, es mantindran les dades '
    . 'relacionades amb l\'usuari nou (per defecte). Això també significa que les '
    . 'dades de l\'usuari vell s\'eliminaran. Si es desmarca, es mantindran les '
    . 'dades relacionades amb l\'usuari vell.';
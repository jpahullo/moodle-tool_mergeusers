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
 <p>Aquest procés usa funcions depenents de la base de dades i pot ser que el seu funcionament
 no estigui del tot comprovat per la vostra base de dades.</p>
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
$string['dbko'] = 'Fusió fallida! <br/>Si la teva base de dades suporta transaccions,
 la teva base de dades no s\'ha modificat.';
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

// Settings page
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

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
$string['description'] =
 '<p>Donat un ID d\'usuari a ser eliminat i un ID a mantenir, aquesta eina fusiona/mou
 les dades relatives de l\'usuari a ser eliminat sobre l\'ID d\'usuari a mantenir.
 És important saber que tots dos IDs existeixin prèviament, i que cap compte s\'eliminarà
 de Moodle. És tasca de l\'administrador de sistema d\'eliminar-lo manualment si s\'escau.</p>
 <p>Aquest procés usa funcions depenents de la base de dades i pot ser que el seu funcionament
 no estigui del tot comprovat per la vostra base de dades. <strong>Recorda que aquesta acció és
 irreversible!</strong></p>';
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
$string['errordatabase'] = 'Error en la base de datos de tipo {$a}';
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

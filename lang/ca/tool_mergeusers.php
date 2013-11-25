<?php

/**
 * Define Catalan language strings
 * @author Jordi Pujol-Ahulló <jordi.pujol@urv.cat> http://www.sre.urv.cat
 * @package    tool
 * @subpackage mergeusers
 * @link http://moodle.org/mod/forum/discuss.php?d=103425
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package admin-tool-mergeusers
 * @version 2013102200
 */

$string['pluginname'] = 'Fusió de comptes d\'usuari';
$string['description'] = '
    <h1>Fusió de dos comptes d\'usuari en un de sol.</h1>
    <p>Donat un ID d\'usuari a ser eliminat i un ID a mantenir, aquesta eina fusiona/mou
    les dades relatives de l\'usuari a ser eliminat sobre l\'ID d\'usuari a mantenir.
    És important saber que tots dos IDs existeixin prèviament, i que cap compte s\'eliminarà
    de Moodle. És tasca de l\'administrador de sistema d\'eliminar-lo manualment si s\'escau.</p>
    <p>Aquest procés usa funcions depenents de la base de dades i pot ser que el seu funcionament
    no estigui del tot comprovat per la vostra base de dades. <strong>Recorda que aquesta acció és
    irreversible!</strong></p>';
$string['errorsameuser'] = 'Tractant de combinar el mateix usuari';
$string['mergeusers'] = 'Fusiona comptes d\'usuari';
$string['merging'] = 'Fusionant';
$string['into'] = 'dins';
$string['newuserid'] = 'ID d\'usuari a mantenir';
$string['olduserid'] = 'ID d\'usuari a eliminar';
$string['mergeusers:view'] = 'Fusió de comptes d\'usuari';
$string['tableok'] = 'Taula {$a} : correctament actualitzada';
$string['tableko'] = 'Taula {$a} : no s\'ha pogut actualitzar correctament!';
$string['dbqueries'] = '<h2>Aquestes són les operacions realitzades a la base de dades</h2>
    <p style="color: #f00;">Si us plau, guardeu aquesta pàgina per futures referències.</p>';
$string['dbok'] = '<h1 style="color:#0c0;">Fusió satisfactòria</h1>';
$string['dbko'] = '<h1 style="color:#f00;">Fusió fallida!</h1>
    <p>Si la teva base de dades suporta transaccions, la teva base de dades no s\'ha modificat.</p>';
$string['tableskipped'] = 'Per guardar registres i seguretat, no processem la taula <strong>{$a}</strong>.
    <br />Per eliminar aquestes entrades, elimina el compte d\'usuari antic una vegada aquesta acció
    hagi finalitzat correctament.';
$string['errordatabase'] = 'Error en la base de datos de tipo {$a}';

<?php

/**
 * Define default English language strings for report
 * @author Forrest Gaston
 * @author Juan Pablo Torres Herrera
 * @author Shane Elliott, Pukunui Technology
 * @author Jordi Pujol-Ahulló, SREd, Universitat Rovira i Virgili
 * @link http://moodle.org/mod/forum/discuss.php?d=103425
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package tool-mergeusers
 */

$string['pluginname'] = 'Fusionner des comptes utilisateur';
$string['header'] = 'Fusionner deux comptes utilisateur en un';
$string['header_help'] =
'<p>Etant donné un utilisateur à supprimer et un utilisateur à conserver, ceci fusionnera
 toutes les données utilisateur vers le compte de l\'utilisateur à conserver. Les deux utilisateurs
 doivent exister dans la base d\'utilisateurs de Moodle, et aucun compte n\'est supprimé par cet utilitaire
 (ceci est laissé au loisir de l\'administrateur).</p>
 <p>Ce procédé utilise certaines fonctions variant d\'un système de bases de données à l\'autre,
 et peut ne pas avoir été testé correctement pour votre type de base de données. </p>
 <p><strong>N\'utilisez ceci que si vous en comprenez les implications, car les opérations réalisées ici ne sont pas
 réversibles !</strong></p>';
$string['usermergingheader'] = '&laquo;{$a->username}&raquo; (ID utilisateur = {$a->id})';
$string['errorsameuser'] = 'Impossible de fusionner le même utilisateur';
$string['mergeusers'] = 'Fusionner des comptes utilisateur';
$string['merging'] = 'Fusion';
$string['into'] = 'vers';
$string['newuserid'] = 'ID de l\'utilisateur à conserver';
$string['olduserid'] = 'ID de l\'utilisateur à supprimer';
$string['mergeusers:view'] = 'Fusionner les comptes utilisateur';
$string['tableok'] = 'Table {$a} : mise à jour OK';
$string['tableko'] = 'Table {$a} : mise à jour PAS OK!';
$string['logok'] = 'Voici les requêtes qui ont été faites sur la base de données:';
$string['logko'] = 'Les erreurs suivantes se sont produites :';
$string['logid'] = 'Pour référence ultérieure, ces données apparaissent dans le dossier avec l\'id {$a}.';
$string['dbok'] = 'La fusion a réussi';
$string['dbko'] = 'La fusion a ECHOUE !<br/>Si votre moteur de base de
 données supporte les transactions, toute l\'opération a été annulée et aucune modification n\'a été
 faite à votre base de données.';
$string['tableskipped'] = 'Pour des raisons de traçabilité ou de sécurité, la table
 <strong>{$a}</strong> n\'est pas traitée.<br />Pour supprimer ces entrées, supprimez l\'ancien
 compte utilisateur une fois la fusion réussie.';
$string['errordatabase'] = 'Type de base de données non supporté : {$a}';
$string['invaliduser'] = 'Utilisateur non valide';
$string['cligathering:description'] = 'Entrez les ID utilisateur à fusionner : le premier (fromid) vers le second (toid).
Les données liées au premier utilisateur seront transférées vers le second, qui intégrera alors toutes les données.';
$string['cligathering:stopping'] = 'Pour interrompre, tapez Ctrl+C ou entrez -1 dans les deux champs (fromid et toid).';
$string['cligathering:fromid'] = 'ID de l\'utilisateur d\'origine    (fromid):';
$string['cligathering:toid'] =   'ID de l\'utilisateur de destination  (toid):';
$string['viewlog'] = 'Voir le journal des fusions';
$string['loglist'] = 'Il s\'agit de la liste des fusions, indiquant pour chacune si elle a été effectuée avec succès:';
$string['newuseridonlog'] = 'ID de l\'utilisateur conservé';
$string['olduseridonlog'] = 'ID d\'utilisateur supprimé';
$string['nologs'] = 'Pas de records de fusion d\'utilisateurs. Bon pour vous!';
$string['wronglogid'] = 'Il n\'existe aucun enregistrement correspondant à votre choix.';
$string['deleted'] = 'L\'utilisateur ID {$a} a été éliminé';


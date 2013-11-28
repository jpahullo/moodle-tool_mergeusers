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
$string['description'] =
'<p>Etant donné un ID utilisateur à supprimer et un ID utilisateur à conserver, ceci fusionnera
 toutes les données utilisateur vers le compte de l\'utilisateur à conserver. Les deux ID utilisateur
 doivent exister dans la base d\'utilisateurs de Moodle, et aucun compte n\'est supprimé par cet utilitaire
 (ceci est laissé au loisir de l\'administrateur).</p>
 <p>Ce procédé utilise certaines fonctions variant d\'un système de bases de données à l\'autre,
 et peut ne pas avoir été testé correctement pour votre type de base de données. <strong>N\'utilisez
 ceci que si vous en comprenez les implications, car les opérations réalisées ici ne sont pas
 réversibles !</strong></p>';
$string['usermergingheader'] = '&laquo;{$a->username}&raquo; (user ID = {$a->id})';
$string['errorsameuser'] = 'Essayer de fusionner le même utilisateur';
$string['mergeusers'] = 'Fusionner des comptes utilisateur';
$string['merging'] = 'Fusion';
$string['into'] = 'vers';
$string['newuserid'] = 'ID utilisateur à conserver';
$string['olduserid'] = 'ID utilisateur à supprimer';
$string['mergeusers:view'] = 'Fusionner les comptes utilisateur';
$string['tableok'] = 'Table {$a} : mise à jour OK';
$string['tableko'] = 'Table {$a} : mise à jour PAS OK!';
$string['logok'] = '<p><strong>Voici les requêtes qui ont été faites sur la base de données</strong><br/>
 Veuillez sauvegarder cette page pour référence.</p>';
$string['logko'] = 'Il ya eu les erreurs suivantes:';
$string['dbok'] = 'La fusion a réussi';
$string['dbko'] = 'La fusion a ECHOUE !<br/>Si votre moteur de base de
 données supporte les transactions, toute l\'opération a été annulée et aucune modification n\'a été
 faite à votre base de données.';
$string['tableskipped'] = 'Pour des raisons de traçabilité ou de sécurité, la table
 <strong>{$a}</strong> n\'est pas traités.<br />Pour supprimer ces entrées, supprimez l\'ancien
 compte utilisateur une fois la fusion réussie.';
$string['errordatabase'] = 'Type de base de données non supporté : {$a}';
$string['invaliduser'] = 'Utilisateur non valide';

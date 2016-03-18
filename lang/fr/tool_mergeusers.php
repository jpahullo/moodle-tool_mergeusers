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
'<p>Etant donné un utilisateur à supprimer et un utilisateur à conserver, ceci fusionnera toutes les données utilisateur vers le compte de l\'utilisateur à conserver. Les deux utilisateurs doivent exister dans la base d\'utilisateurs de Moodle, et aucun compte n\'est supprimé par cet utilitaire (ceci est laissé au loisir de l\'administrateur).</p><p><strong>N\'utilisez ceci que si vous en comprenez les implications, car les opérations réalisées ici ne sont pas réversibles !</strong></p>';
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
$string['logid'] = 'Pour référence ultérieure, ces données apparaissent dans le journal avec l\'id {$a}.';
$string['dbok'] = 'La fusion a réussi';
$string['dbko_transactions'] = '<strong>La fusion a échoué !</strong> <br/>Votre moteur de base de données supporte les transactions. Par conséquent, la base de données <strong>n\'a pas été modifiée</strong>.';
$string['dbko_no_transactions'] = '<strong>La fusion a échoué !</strong> <br/> Votre moteur de base de données pas supporte les transactions. Par conséquent, votre base de données <strong>a été modifiéé</strong> et a été laisséé dans un état incohérent. <br/>Vérifiez le journal de la fusion et signalez les erreurs aux développeurs de plugin.<br/> Une fois le plugin corrigé par les développeurs et mis à jour, réitérez la fusion pour finaliser.';
$string['tableskipped'] = 'Pour des raisons de traçabilité ou de sécurité, la table <strong>{$a}</strong> n\'est pas traitée.<br />Pour supprimer ces entrées, supprimez l\'ancien compte utilisateur une fois la fusion réussie.';
$string['errordatabase'] = 'Erreur: Type de base de données {$a} non supporté.';
$string['invaliduser'] = 'Utilisateur non valide';
$string['cligathering:description'] = 'Entrez les ID utilisateur à fusionner : le premier (fromid) vers le second (toid). Les données liées au premier utilisateur seront transférées vers le second, qui intégrera alors toutes les données.';
$string['cligathering:stopping'] = 'Pour interrompre, tapez Ctrl+C ou entrez -1 dans les deux champs (fromid et toid).';
$string['cligathering:fromid'] = 'ID de l\'utilisateur d\'origine (fromid):';
$string['cligathering:toid'] =   'ID de l\'utilisateur de destination (toid):';
$string['viewlog'] = 'Voir le journal des fusions';
$string['loglist'] = 'Il s\'agit de la liste des fusions, indiquant pour chacune si elle a été effectuée avec succès:';
$string['newuseridonlog'] = 'ID de l\'utilisateur conservé';
$string['olduseridonlog'] = 'ID d\'utilisateur supprimé';
$string['nologs'] = 'Pas de journaux de fusion d\'utilisateurs. Bon pour vous !';
$string['wronglogid'] = 'Il n\'existe aucun enregistrement correspondant à votre choix.';
$string['deleted'] = 'L\'utilisateur ID {$a} a été éliminé';
$string['errortransactionsonly'] = 'Erreur: Le support des transactions est requis, et votre base de données {$a} ne les supporte pas. Si nécessaire, vous pouvez configurer ce module pour que les fusions sont faites sans utiliser les transactions. Ajustez les paramètres en fonction de vos besoins.';
$string['eventusermergedsuccess'] = 'Fusionné succès';
$string['eventusermergedfailure'] = 'Fusionné échoué';

//New strings

// Progress bar
$string['choose_users'] = 'Sélectionnez les utilisateurs à fusionner';
$string['review_users'] = 'Confirmez utilisateurs à fusionner';
$string['results'] = 'Résultat de la fusion';

// Settings page
$string['transactions_setting'] = 'Seules les transactions sont autorisées';
$string['transactions_setting_desc'] = 'Si cette option est activée, les comptes
    utilisateur ne peuvent être fusionnés que si votre base de données prend en
    charge les transactions (recommandé). Avec cette option activée, vous vous
    assurez que la base de données reste toujours dans un état cohérent, même si
    une fusion se termine avec des erreurs.<br /> Si cette option est désactivée,
    vous pourrez fusionner des comptes utilisateur sans utiliser de transactions.
    En cas d\'erreur, l\'inscription de la fusion montrera quel était le problème.
    Si vous signalé cette erreur aux développeurs de ce plugin, une solution devrait
    être trouvée rapidement.<br />Notez que ce plugin gère tous les composants
    standard de Moodle. Par conséquent, si vous avez une installation de Moodle
    standard, vous pouvez exécuter ce plugin sans problème avec cette option activée
    ou désactivée.';
$string['transactions_supported'] = 'Pour votre information, votre base de données
    <strong>prend en charge les transactions</strong>.';
$string['transactions_not_supported'] = 'Pour votre information, votre base de
    données <strong>ne prend pas en charge les transactions</strong>.';
$string['excluded_exceptions'] = 'Exceptions à exclure';
$string['excluded_exceptions_desc'] = 'L\'expérience dans ce domaine suggère que
    ces tables de base de données doivent être exclues du processus fusion. Voir
    le fichier README pour plus de détails.<br>Donc, si vous voulez appliquer le
    comportement par défaut, vous devez choisir \'{$a}\' afin d\'exclure ces tables
    du processus de fusion (recommandé).<br>Si vous préférez, vous pouvez choisir
    les tables que vous souhaitez inclure dans le processus de fusion
    (non recommandé).';

// quiz attempts strings
$string['quizattemptsaction'] = 'Résoudre les tentatives d\'un questionnaire';
$string['quizattemptsaction_desc'] = 'Le questionnaire tentative de fusion peut se produire l\'un des trois
    situations :
    <ol>
    <li>Ancien utilisateur est celui qui a essayé le questionnaire. Tout nouvel utilisateur de se déplacer comme si elle
    il les a effectués.</li>
    <li>Le nouvel utilisateur est le seul qui essaie questionnaire. Rien à faire, parce que c\'est tout droit.</li>
    <li>Les utilisateurs fait des tentatives pour le même questionnaire. <strong>Dans ce cas s\'applique lorsque le
    l\'action que vous choisissez ce paramètre</strong>. Les actions peuvent être:
        <ul>
        <li><strong>{$a->renumber}</strong>. Les tentatives de l\'ancien utilisateur est ajouté au nouvel
        utilisateur et ils sont renumérotés par l\'heure de début de chaque tentative.</li>
        <li><strong>{$a->delete_fromid}</strong>. Sont autorisés uniquement les tentatives par le nouvel utilisateur et
        supprimer les tentatives de l\'ancien utilisateur. Par conséquent, les récentes tentatives de faire prévaloir.</li>
        <li><strong>{$a->delete_toid}</strong>. Les tentatives sont laissés seulement l\'ancien utilisateur
        et supprimer le nouvel utilisateur. Voici la première prévaut.</li>
        <li><strong>{$a->remain}</strong> (par défaut). Les tentatives restent liés utilisateur qui a généré sans
        les fusionner ou de les supprimer. C\'est l\'option la plus sûre si les effets ne sont pas connus, mais
        peut générer des notes différentes selon le questionnaire fusion utilisateur de A à B ou de B à A.</li>
        </ul>
    </li>
    </ol>';
$string['qa_action_renumber'] = 'Fusionner toutes les intentions et réénumérer';
$string['qa_action_delete_fromid'] = 'Supprimer les anciennes tentatives de l\'utilisateur';
$string['qa_action_delete_toid'] = 'Supprime les nouvelles tentatives de l\'utilisateur';
$string['qa_action_remain'] = 'Gardez tentatives intacts sans fusionner ou supprimer';
$string['qa_action_remain_log'] = 'Les données utilisateur restent intacts dans le tableau <strong>{$a}</strong>.';
$string['qa_chosen_action'] = 'Option active pour tentatives de questionnaire: {$a}.';

$string['qa_grades'] = 'Notes recalculées pour les questionnaires: {$a}.';

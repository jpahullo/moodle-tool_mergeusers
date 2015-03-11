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

//New strings

// Progress bar
$string['choose_users'] = 'Sélectionnez les utilisateurs à fusionner';
$string['review_users'] = 'Confirmez utilisateurs à fusionner';
$string['results'] = 'Résultat de la fusion';

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

// Settings page
$string['suspenduser'] = 'Suspendre utilisateur de supprimer';
$string['suspenduser_desc'] = 'Si elle est activée, l\'utilisateur de supprimer
     sera automatiquement suspendu si la fusion se termine avec succès,
     qui permettra d\'éviter authentifier sur Moodle (recommandé).
     Si désactivé, vous supprimez restera actif.
     Dans les deux cas, l\'utilisateur ne sera pas avoir à retirer ses données
     activité Moodle.';
$string['transactions'] = 'Seules les transactions sont autorisées';
$string['transactions_desc'] = 'Si cette option est activée, les comptes
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
$string['tablemerger_settings'] = 'Réglages pour la fusion des tables';
$string['tablemerger_settings_desc'] = 'Ce plugin met en œuvre plusieurs éléments
    de fusionner des enregistrements à partir de tables de base de données, et ainsi
    fusionner de utilisateur. La configuration spécifique ci-dessous.';
$string['cronsettings'] = 'Configuration de cron';
$string['cronsettings_help'] = 'Vous pouvez définir un outil pour obtenir la liste
    des utilisateurs de fusionner. Par défaut, ce plugin fournit une CLIGathering
    outil interactif.<br>
    Au lieu de cela, vous pouvez définir votre outil non-interactif, placez
    le script de CLI dans cron serveur de processus afin que les utilisateurs
    de fusionner automatiquement.<br>
    Pour ce faire, vous devez développer votre rassemblement de classe qui
    implémente l\'interface. Cette classe sera básicament un itérateur qui
    à chaque itération, il retourne un objet avec des attributs \'fromid\'
    et \'toid\' qui identifie les utilisateurs de fusionner avec leur \'user.id\'.';
$string['cronsettings_desc'] = 'Si vous placez le script dans cron serveur CLI
    et vous fournir un outil de rassemblement non itérative, vous pouvez
    automatiquement fusion utilisateurs. Par défaut, CLIGathering est un
    outil interactif et ne sert pas cet objectif. Visitez aide pour en savoir plus.';
$string['gathering'] = 'Liste d\'outils';
$string['gathering_desc'] = 'Rassemblement outil est essentiellement une liste
    iterator. A chaque itération, il fournit un objet avec des attributs \'fromid\'
    et \'toid\' identifier les utilisateurs qui à fusionner.';
$string['exclude_tables_settings'] = 'Exclut fusionnent tables';
$string['exclude_tables_settings_help'] = 'Quand une table de base de données est exclue
    les utilisateurs de processus de fusion de données sont autorisés à ce plugin votre
    le traitement, donc, des tableaux sélectionnés sont laissées intactes.
    Étrangement, cela est nécessaire dans certains cas. <br>
    Notre expérience nous dit que
    les tables de base de données suivantes auraient à exclure lors de la fusion
    utilisateurs et ainsi fournissent également le comportement par défaut de cette
    plugin: my_pages, user_info_data, user_preferences, user_private_key. Regarder
    README pour plus de détails techniques. <br>
    En fait, toujours my_pages devraient exclure, pour quand un utilisateur
    a plus d\'un enregistrement de cette table, faire Mon Moodle inopérable.';
$string['exclude_tables_settings_desc'] = 'Sélectionnez les tables de base de données
    doit être exclu que lors de la fusion des utilisateurs.';
$string['excluded_tables'] = 'Tables à l\'exclusion';
$string['excluded_tables_desc'] = 'Tables à l\'exclusion de la fusion des utilisateurs.';
$string['tablesettings'] = 'Tables et colonnes liées à user.id';
$string['tablesettings_help'] = 'Cette section est très inportant, et vous aime
    Administrateur doit être très prudent avec lui.<br>
    Ici vous avez la possibilité de définir les noms de colonnes
    dont ils sont user.id. de colonne liées Et vous pouvez le faire de deux façons.
    La première façon est de définir une liste commune et <strong> nom générique
    colonne</strong> que si présente <strong>toujours et rien ne sera liée
    avec user.id</strong> colonne, indépendamment de la table où vous êtes.
    La deuxième façon est de définir <strong>une liste des tables avec des noms
    seule colonne spécifique et lié à user.id</strong>.<br>
    Prenant tout cela configuré, ce plugin va vérifier tout de base
    Moodle et fusionner les données de deux utilisateurs ne tenant compte que: <ul>
    <li> Ces tableaux avec les noms de colonnes spécifiques.</li>
    <li> Le reste des tableaux qui envisagent de noms de colonnes génériques.</li>
    </ul> Ceci est la raison pour laquelle cette configuration importate refléter
    et intégrer tout nom de colonne qui est lié à user.id.';
$string['specifiedtablesettingsoperation'] = 'Pour définir les noms des
    colonne spéciale pour les tables sélectionnées, <strong>vous avez à
    visiter cette page deux paramètres</strong> et procéder comme
    suit:<ol>
    <li>Sélectionnez tableaux avec les noms de colonnes relacionades user.id
    personalitzado et enregistrer les paramètres. </li>
    <li>Revisitez cette page et de remplir la liste des colonnes connexes
    avec user.id pour chaque table sélectionnée ci-dessus. Enfin, gardez
    configuration à nouveau.</li>
    </ol>Cette configuration est une priorité plus élevée que la liste générique
    les noms de colonnes.';
$string['user_related_columns_for_default_setting'] = 'Genéricament liés colonnes user.id';
$string['user_related_columns_for_default_setting_desc'] = 'Tous les noms
    colonne de votre base de données Moodle figurent dans cette liste. choisir
    ceux <strong>devrait apparaître sur aucune liste sera user.id
    connexes</strong>.';
$string['tables_with_custom_user_related_columns'] = 'Tables avec des noms
    colonne liés user.id spécifique';
$string['tables_with_custom_user_related_columns_desc'] = 'Tous les tableaux
    votre base de données Moodle figurent dans cette liste.
    électionnez les tables qui ont des noms de colonnes uniques
    user.id. connexes Devrait différer de ceux qui se posent
    dans la liste générique.';
$string['user_related_columns_for_table_setting_desc'] = 'Choisissez tout
    les noms de colonnes dans ce tableau qui sont liés à user.id.';
$string['unique_indexes_settings'] = 'Indices composites uniques';
$string['unique_indexes_settings_desc'] = 'Ceci est la liste des <strong>index
    ne fait qu\'aggraver</strong> base de données Moodle avec une colonne
    user.id. connexes Tous ces indices sont traitées par cette lorsque deux
    utilisateurs plugin d\'fusible. Comme seuls les indices composites ne
    permettent pas plusieurs enregistrements avec les mêmes valeurs de l\'indice,
    Ce plugin gère cette multiplicité avant la mise à jour base de données.<br>
    La liste contine noms de tables, les index et les colonnes défini. Colonnes
    en surbrillance concernent user.id.';
$string['table'] = 'Table de base de données';
$string['index'] = 'Index';
$string['columns'] = 'Liste ordonnée de colonnes qui composent l\'index';
$string['nonunique_index_settings'] = 'Index non seulement composés';
$string['nonunique_index_settings_help'] = 'Tous les tarifs indiqués dans cette
    section, ils ne sont pas uniques. Cela signifie que, par défaut, votre base
    de données permet à plusieurs enregistrements avec les mêmes valeurs pour
    chaque index.<br>
    Cependant, il ya des cas où il n\'a pas de sens de garder des enregistrements
    différents quand, dans notre cas, reportez-vous à la même personne lorsque vous
    fusionner deux utilisateurs de Moodle. Donc, dans cette section, vous pouvez
    choisir les indices composites d\'être traitées comme si elles étaient uniques.
    Par conséquent, il ne permet pas de plus d\'un enregistrement avec les mêmes
    valeurs par l\'indice, <strong>toujours sans modifier la structure de votre
    base de données</strong>.';
$string['nonunique_index_operation'] = 'Ensuite, vous pouvez décider de ce taux
    non seulement des composés devraient être poursuivis comme si elles étaient
    seulement <strong>sans changer la structure de votre base de données</strong>.
    Pour ce faire, vous devez suivre ces étapes senzillos:<ol>
    <li>Sélectionnez dans la liste les index non-uniques pour traiter cette
    Plugin comme si elles étaient uniques.</li>
    <li>Enregistrer les paramètres.</li>
    </ol> Les valeurs par défaut avec <strong>Oui</strong> définit le
    le comportement par défaut de ce plugin.<br>
    Les indices sont décrits indiquant
    <strong> {nom de la table} - {nom d\'index} : {} {column1}, {column2} [, ...]</strong>.
    Les colonnes marquées sur les valeurs référencées au user.id de colonne.';
$string['tables_with_adhoc_indexes_settings'] = 'Comme indices composites définis';
$string['tables_with_adhoc_indexes_settings_help'] = 'La structure de base de
    données actuelles ne peuvent pas contenir les indices composites nécessaires
    pour une fusion d\'utilisateurs adéquats et significatifs. <br>
    Pour résoudre ce problème, <strong>sans changer la structure du votre base
    de données</strong>, vous permettent de définir des indices composites ici
    comme. Ce plugin utilise les index pour identifier les données en double.<br>
    Pour définir des index composites suffit de suivre ces étapes: sélectionnez
    la liste des tables sur la nécessité de définir des index, conserver les
    paramètres, puis vous définissez les colonnes qui composent l\'indice
    au sein de chaque tableau, et de garder à nouveau les paramètres.
    Colonne sélectionnée devrait être liée à user.id.';
$string['tables_with_adhoc_indexes_settings_desc'] = 'Vous pouvez définir des index
    composés comme <strong>sans changer la structure de votre base de données
    données</strong> en suivant ces étapes:<ol>
    <li>Dans la liste des tables, choisissez ceux qui définissent la comme des
    indices.</li>
    <li>Enregistrer les paramètres.</li>
    <li>Pour chaque table sélectionnée définit les colonnes qui composent
    l\'indice. </ li>
    <li> Enregistrer les paramètres à nouveau.</li>
    </ol>';
$string['tables_with_adhoc_indexes'] = 'Comme les tables avec des index';
$string['tables_with_adhoc_indexes_desc'] = 'Définir tables qui devraient avoir
    que les indices composites.';
$string['columns_for_adhoc_index_for_table_setting_desc'] = 'Définir colonnes
     qui formera le nouvel indice composite de mesurer ce tableau.';
$string['check_indexes_settings'] = 'Vérification index';
$string['check_indexes_settings_desc'] = 'Ensuite, la liste apparaît à la fois
    de votre propre base de données, tels que les taux plein vous avez défini
    comme ci-dessus si vous le besoin. Tous ont au moins une colonne associé à
    user.id. La liste indique: les noms des tables, index, type de index et
    index des colonnes dans l\'ordre de la définition. Colonnes vous balisés
    sont liés à la user.id. <strong>Ce plugin utilise ces indices pour la fusion
    correctement utilisateur</strong>.<br>
    Si vous pensez que vous manquez un indice ou une fusion d\'utilisateurs que
    vous a montré une erreur, vous devez revoir la configuration ci-dessus à
    propos de <strong>colonnes de user.id liés</strong> dans toutes ses formes.
    Ensuite, vous devriez vérifier à nouveau la liste des index uniques et
    non-uniques, si il semble que vous avez besoin, ou définir votre propre index
    mesurer. En fin de compte, vous devriez voir dans cette liste ci-dessous que
    vous index il avait besoin et vous permet désormais de fusionner utilisateurs
    normalement.<br>
    Aller avec soin lors de la mise à jour de la configuration.';
$string['noindexes'] = 'Nous ne avons pas trouvé des indices composites avec
    des champs utilisateurs de Moodle liés. Il est très étrange. Vous devez
    vérifier seriosament la structure de votre base de données.';
$string['uniqueness'] = 'Unicité';
$string['uniqueness0'] = 'Non unique';
$string['uniqueness1'] = 'Unique';
$string['uniqueness2'] = 'Fait sur mesure';

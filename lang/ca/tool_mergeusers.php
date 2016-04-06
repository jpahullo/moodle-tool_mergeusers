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

// Settings page
$string['suspenduser'] = 'Suspendre usuari a eliminar';
$string['suspenduser_desc'] = 'Si s\'activa, l\'usuari a eliminar
    es suspendrà automàticament si la fusió conclou satisfactòriament,
    la qual cosa evitará que l\'usuari s\'autentiqui a Moodle (recomanat).
    Si es desactiva, l\'usuari a eliminar romandrà actiu. En ambdós casos,
    l\'usuari es quedarà sense les seves dades i la seva activitat de Moodle.';
$string['transactions'] = 'Només transaccions';
$string['transactions_desc'] = 'Si s\'activa, la fusió d\'usuaris no
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
$string['tablemerger_settings'] = 'Configuració per la fusió de taules';
$string['tablemerger_settings_desc'] = 'Aquest plugin implementa varis fusionadors
    de taules de base de dades per processar la fusió d\'usuaris. La seva
    configuració específica apareix a continuació.';
$string['cronsettings'] = 'Configuració pel cron';
$string['cronsettings_help'] = 'Pots proveir aquí l\'eina per recollir el llistat d\'usuaris
    a fusionar. Per defecte, aquest plugin proveeix CLIGathering que és una eina
    interactiva.<br>
    En canvi, pots definir la teva eina no interactiva implementant la teva classe que
    implementi la interfície Gathering. Aquesta classe és bàsicament un
    iterador que a cada iteració proveeix un object amb atributs \'fromid\'
    i \'toid\', que identifiquen els usuaris a fusionar mitjançant els seus \'user.id\'.
    Aleshores, col·loca l\'script CLI al cron del servidor per processar
    la fusió dels usuaris automàticament.';
$string['cronsettings_desc'] = 'Si col·loques l\'script CLI al cron del servidor
    i proveeixes una eina Gathering no iteractiva, pots realitzar la fusió
    d\'usuaris automàticament. Per defecte, CLIGathering és una eina
    interactiva i no serveix per tal efecte. Visita l\'ajuda per saber-ne més.';
$string['gathering'] = 'Eina de col·lecta';
$string['gathering_desc'] = 'L\'eina de col·lecta Gathering és bàsicament un iterador.
    A cada iteració aquest proveeix un objecte amb els atributs \'fromid\' i \'toid\'
    que identifiquen els usuaris a fusionar.';
$string['exclude_tables_settings'] = 'Exclou taules a fusionar';
$string['exclude_tables_settings_help'] = 'Quan s\'exclou una taula de base de
    dades del procés de fusió d\'usuaris es prohibeix a aquest plugin que la
    processi. Amb la qual cosa, les taules seleccionades romanen intactes.
    Tot i que pugui semblar estrany, això és necessari en certs casos.<br>
    La nostra experiència ens diu que
    les següents taules de base de dades s\'haurien d\'excloure de la fusió
    d\'usuaris i així, també, tenir el comportament per defecte a aquest
    plugin: my_pages, user_info_data, user_preferences, user_private_key. Veure
    README per més detalls tècnics. <br>
    De fet, my_pages sempre s\'hauria d\'excloure, ja que si un usuari disposa
    de diferents registres en aquesta taula fa que El meu Moodle deixi de funcionar.';
$string['exclude_tables_settings_desc'] = 'Selecciona les taules de base de dades'
        . ' que s\'han d\'excloure durant la fusió d\'usuaris.';
$string['excluded_tables'] = 'Taules excloses';
$string['excluded_tables_desc'] = 'Taules excloses de la fusió d\'usuaris.';
$string['tablesettings'] = 'Taules i columnes relacionades a user.id';
$string['tablesettings_help'] = 'Aquesta secció és molt important, i tu com a
    administrador has de tenir molta cura amb ella.<br>
    A sota tens la possibilitat de definir els noms de columna que es relacionen
    amb la columna user.id. Tens dues maneres de definir-los.
    La primera manera és definint un llistat comú i <strong>genèric de noms
    columna</strong> que, si apareixen <strong>sempre i només estaran vinculats
    a la columna user.id</strong>, independentment de la taula on es trobi.
    La segona manera és definint <strong>un llistat de taules amb noms de
    columna específics i únics lligats a user.id</strong>.<br><br>
    Tenint tot això configurat, aquest plugin et comprovarà tota la base de
    dades de Moodle i fusionarà dos usuaris considerant només:
    <ul>
    <li>Aquestes taules amb noms de columna específics.</li>
    <li>La resta de taules considerant els noms de columna genèrics.</li>
    </ul>
    Per això és molt important que aquesta configuració reflecteixi i inclogui
    tot nom de columna que estigui relacionat amb user.id.';
$string['specifiedtablesettingsoperation'] = 'Per tal de designar els noms de
    columna particulars per les taules seleccionades, <strong>has de visitar
    aquesta pàgina de configuració dues vegades</strong> i fer el següent:
    <ol>
    <li>Sel·leccionar les taules amb noms de columna relacionat amb user.id
    personalitzat i guardar la configuració.</li>
    <li>Revisitar aquesta pàgina i emplenar el llistat de columnes relacionats
    amb user.id per cada taula sel·leccionada anteriorment. En acabar,
    guardar la configuració de nou.</li>
    </ol>Aquesta configuració és més prioritària que el llistat genèric de noms de columna.';
$string['user_related_columns_for_default_setting'] = 'Columnes genèricament relacionades amb user.id';
$string['user_related_columns_for_default_setting_desc'] = 'Tots els noms de
    columna de la base de dades de Moodle apareixen en aquest llistat. Tria\'n
    aquells que, <strong>en cas d\'aparèixer en qualsevol taula sempre estaran
    relacionats amb user.id</strong>.';
$string['tables_with_custom_user_related_columns'] = 'Taules amb noms de columna específics relacionats amb user.id';
$string['tables_with_custom_user_related_columns_desc'] = 'Totes les taules
    de la base de dades de Moodle apareixen en aquest llistat.
    Sel·lecciona aquelles taules que disposin de noms de columna exclusius
    relacionats amb user.id, i que haurien de diferir d\'aquells que s\'han
    triat dins el llistat genèric.';
$string['user_related_columns_for_table_setting_desc'] = 'Tria tots els noms
    de columna d\'aquesta taula que estiguin relacionats amb user.id.';
$string['unique_indexes_settings'] = 'Índexos compostos únics';
$string['unique_indexes_settings_desc'] = 'Aquest és el llistat d\'<strong>índexos
    compostos únics</strong> de la base de dades de Moodle amb alguna columna
    relacionada amb user.id. Tots aquests índexos són processats per aquest
    plugin quan es fusionen dos usuaris. Com que els índexos compostos únics
    no permeten múltiples registres amb els mateixos valors a l\'índex,
    aquest plugin gestiona aquesta multiplicitat abans d\'actualitzar la
    mateixa base de dades.<br><br>
    Al llistat hi ha els noms de taula, dels índexos i de les columnes que
    hi aparèixen. Les columnes remarcades estan relacionades amb user.id.';
$string['table'] = 'Taula de base de dades';
$string['index'] = 'Índex';
$string['columns'] = 'Columnes de l\'índex en l\'ordre de definició';
$string['nonunique_index_settings'] = 'Índexos compostos no únics';
$string['nonunique_index_settings_help'] = 'Tots els índexos que apareixen
    en aquesta secció són no únics. Això significa que per defecte, la teva
    base de dades permet múltiples registres amb els mateixos valors per un
    índex concret.<br><br>
    No obstant, hi ha casos on no té sentit mantenir diferents registres quan,
    en el nostre cas, fan referència a la mateixa persona quan se li estan
    fusionant dos usuaris de Moodle. Proveïm doncs aquesta secció perquè
    triïs els índexos compostos a ser processats com si ells fossin únics.
    Això vol dir que no es permetrà més d\'un registre amb els mateixos valors
    per un índex donat, <strong>sempre sense modificar l\'estructura de la teva
    base de dades</strong>.';
$string['nonunique_index_operation'] = 'A continuació pots decidir quins
    índexos compostos no únics poden ser processats com si fossin únics
    <strong>sense modificar l\'estructura de la teva base de dades</strong>.
    Per fer-ho has de seguir els següents senzills passos:<ol>
    <li>Selecciona d\'aquesta llista els índexos no únics a ser processats
    per aquest plugin.</li>
    <li>Guarda la configuració.</li>
    </ol>Els valors per defecte amb <strong>sí</strong> defineixen el
    comportament per defecte d\'aquest plugin.<br><br>
    Els índexos apareixen descrits indicant-ne
    <strong>{nom de la taula} - {nom de l\'índex} : {columna1}, {columna2}[, ...]</strong>.
    Les columnes remarcades fan referència a valors del user.id.';
$string['tables_with_adhoc_indexes_settings'] = 'Defineix índexos compostos a mida';
$string['tables_with_adhoc_indexes_settings_help'] = 'L\'estructura de base de
    dades actual pot no contenir els índexos compostos necessaris per poder
    realitzar una fusió d\'usuaris adequada i amb sentit.<br><br>
    Per solucionar aquesta mancança, <strong>sense modificar l\'estructura de la
    teva base de dades</strong>, et permetem que defineixis aquí índexos compostos
    a mida. Aquest plugin usa els índexos per identificar dades duplicades.<br><br>
    Per definir índexos compostos a mida només cal que segueixis aquests passos:
    selecciones les taules del llistat sobre les que necessites definir-ne índexos,
    guarda la configuració, aleshores defineix per cada taula les columnes que
    conformaran l\'índex, i guarda un altre cop la configuració actual.
    Una columna de les seleccionades ha d\'estar relacionada amb user.id.';
$string['tables_with_adhoc_indexes_settings_desc'] = 'Pots definir
    índexos compostos a mida <strong>sense modificar l\'estructura de la teva
    base de dades</strong> seguint els següents passos:<ol>
    <li>De la llista de taules, tria\'n totes les que hagin de tenir índexos
    a mida.</li>
    <li>Guarda la configuració.</li>
    <li>Per cada taula seleccionada, tria les columnes que conformaran
    l\'índex.</li>
    <li>Guarda la configuració altra vegada.</li>
    </ol>';
$string['tables_with_adhoc_indexes'] = 'Taules amb índexos a mida';
$string['tables_with_adhoc_indexes_desc'] = 'Tria les taules que han de tenir
    índexos compostos a mida.';
$string['columns_for_adhoc_index_for_table_setting_desc'] = 'Tria les columnes
    que definiran el nou índex compost fet a mida per aquesta taula.';
$string['check_indexes_settings'] = 'Comprovació dels índexos';
$string['check_indexes_settings_desc'] = 'A continuació apareix el llistat
    d\'índexos provinents de la definició de la teva base de dades, així com
    també aquells definits a mida si s\'escau. Tots ells tenen com a mínim
    una columna relacionada amb user.id.
    El llistat mostra els noms de les taules, dels índexos, el tipus d\'índex
    i les columnes de l\'índex. Les columnes remarcades estan relacionades
    amb user.id.
    <strong>Aquest plugin usa aquests índexos per realitzar adequadament
    la fusió dels usuaris</strong>.<br><br>
    Si creus que hi manca algún índex, o bé t\'has trobat amb
    un error quan fusionaves dos usuaris, hauries de revisar la configuració
    de dalt per <strong>les columnes relaciones amb user.id</strong> en
    qualsevol de les dues formes mostrades. Després, hauries de revisar de nou
    el llistat d\'índexos únics, no únics per si apareix allò que necessites,
    o bé definir el teu propi índex a mida. En acabar, hauries de veure a sota
    aquell índex que et feia falta i que et permet fusionar els usuaris amb
    normalitat.<br><br>
    Sigues molt cautelós quan actualitzis la configuració.';
$string['noindexes'] = 'No s\'han trobat índexos compostos amb columnes
    relacionades amb user.id. Això és molt estrany. <strong>Seriosament,
    hauries de revisar la definició i estructura de la teva base de dades</strong>.';
$string['uniqueness'] = 'Unicitat';
$string['uniqueness0'] = 'No únic';
$string['uniqueness1'] = 'Únic';
$string['uniqueness2'] = 'A mida';

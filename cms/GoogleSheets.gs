/**
 * Récepteur facultatif à installer dans le Google Sheets privé de simulation.
 * Extensions > Apps Script : coller ce fichier, enregistrer, exécuter onOpen.
 * La démonstration fonctionne d'abord avec les exports JSON/CSV locaux.
 */
function onOpen() {
  SpreadsheetApp.getUi().createMenu('FECGD')
    .addItem('Importer un export JSON local', 'importerJson')
    .addToUi();
}

function importerJson() {
  const ui = SpreadsheetApp.getUi();
  const result = ui.prompt('Importer les données locales', 'Collez le contenu du fichier fecgd-statistiques.json exporté dans WordPress.', ui.ButtonSet.OK_CANCEL);
  if (result.getSelectedButton() !== ui.Button.OK) return;
  try {
    const data = JSON.parse(result.getResponseText());
    appliquer(data, SpreadsheetApp.getActive());
    ui.alert('Les données locales et les statistiques ont été importés.');
  } catch (error) { ui.alert('Import impossible : ' + error.message); }
}

function appliquer(data, spreadsheet) {
  if (!['local-validation', 'simulation'].includes(data.mode)) throw new Error('Cet export ne provient pas de la simulation.');
  const tabs = {visites:'Visites', inscriptions:'Inscriptions', pages:'Pages', sources:'Sources'};
  // Vérifier tout le document avant d'écrire la moindre feuille.
  Object.keys(tabs).forEach(key => {
    if (!Array.isArray(data[key]) || data[key].length > 995) throw new Error('Export absent ou trop volumineux : ' + key);
    if (!spreadsheet.getSheetByName(tabs[key])) throw new Error('Onglet absent : ' + tabs[key]);
    const width = key === 'visites' ? 5 : key === 'inscriptions' ? 6 : 3;
    if (!data[key].every(row => Array.isArray(row) && row.length === width)) throw new Error('Colonnes incorrectes : ' + key);
  });
  Object.keys(tabs).forEach(key => {
    const sheet = spreadsheet.getSheetByName(tabs[key]);
    const width = data[key][0].length;
    const rows = data[key].slice(1).map(row => row.map((value, index) => {
      if ((key === 'inscriptions' && index === 1) || (key !== 'inscriptions' && index === 0)) {
        const date = String(value).slice(0,10).split('-').map(Number);
        if (date.length !== 3 || !date.every(Number.isFinite)) throw new Error('Date invalide');
        return new Date(date[0], date[1]-1, date[2],12);
      }
      // Échapper les textes pouvant être interprétés comme formules.
      return typeof value === 'string' && /^[=+@\-]/.test(value) ? "'" + value : value;
    }));
    sheet.getRange(5,1,995,width).clearContent();
    if (rows.length) sheet.getRange(5,1,rows.length,width).setValues(rows);
    if (rows.length) sheet.getRange(5,key==='inscriptions'?2:1,rows.length,1).setNumberFormat('dd/mm/yyyy');
    sheet.getRange('A2').setValue('Données locales importés le ' + Utilities.formatDate(new Date(),'Africa/Kinshasa','dd/MM/yyyy HH:mm'));
  });
  const board=spreadsheet.getSheetByName('Tableau de bord');
  board.getRange('A2').setValue('Statistiques du site en validation locale. Instantané importé ; aucune mesure du site public.');
  board.getRange('A13').setValue(new Date(new Date().getFullYear(),new Date().getMonth(),1,12));
  const sep=spreadsheet.getSpreadsheetLocale().startsWith('fr')?';':',';
  board.getRange('B13').setFormula('=COUNTIFS(Inscriptions!B5:B1000'+sep+'">="&A13'+sep+'Inscriptions!B5:B1000'+sep+'"<"&EDATE(A13'+sep+'1))');
  const count=data.visites.length-1;
  board.getRange('D22:E1000').clearContent();
  board.getRange('D22:E22').setValues([['Jour','Visites']]);
  if (count) {
    const formulas=Array.from({length:count},(_,i)=>['=TEXT(Visites!A'+(i+5)+sep+'"dd/mm")','=Visites!C'+(i+5)]);
    board.getRange(23,4,count,2).setFormulas(formulas);
    const charts=board.getCharts();
    if (charts.length) board.updateChart(charts[0].modify().clearRanges().addRange(board.getRange(22,4,count+1,2)).build());
  }
}

/**
 * Option de synchronisation depuis WordPress :
 * 1. Définir SHEET_ID et FECGD_TOKEN dans les propriétés de ce script.
 * 2. Déployer comme application web, exécutée sous votre compte.
 * 3. Copier l'URL /exec et le secret dans Gestion FECGD > Paramètres.
 * L'URL n'est pas un accès public au tableau. Le secret est requis pour toute écriture.
 */
function doPost(event) {
  let lock;
  try {
    const payload=JSON.parse(event.postData.contents);
    const props=PropertiesService.getScriptProperties();
    const token=props.getProperty('FECGD_TOKEN');
    if (!token || token.length<32 || payload.token!==token) throw new Error('Accès refusé');
    lock=LockService.getScriptLock();lock.waitLock(10000);
    appliquer(payload.data,SpreadsheetApp.openById(props.getProperty('SHEET_ID')));
    return ContentService.createTextOutput(JSON.stringify({ok:true})).setMimeType(ContentService.MimeType.JSON);
  } catch (error) {
    return ContentService.createTextOutput(JSON.stringify({ok:false,error:'Import refusé ou invalide'})).setMimeType(ContentService.MimeType.JSON);
  } finally {if(lock)lock.releaseLock();}
}

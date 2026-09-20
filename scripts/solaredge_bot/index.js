require('dotenv').config();
const TelegramBot = require('node-telegram-bot-api').default || require('node-telegram-bot-api');
const axios = require('axios');
const fs = require('fs');
const mqtt = require('mqtt');

// Variables d'entorn
const TELEGRAM_TOKEN = process.env.TELEGRAM_TOKEN || process.env.TELEGRAM_BOT_TOKEN;
const CHAT_ID = process.env.CHAT_ID || process.env.TELEGRAM_CHAT_ID;
const TELEGRAM_TOPIC_ID = process.env.TELEGRAM_TOPIC_ID ? parseInt(process.env.TELEGRAM_TOPIC_ID, 10) : null;
const SOLAREDGE_SITE_ID = process.env.SOLAREDGE_SITE_ID;
const SOLAREDGE_API_KEY = process.env.SOLAREDGE_API_KEY;

const MQTT_HOST = process.env.MQTT_BROKER_HOST || 'mosquitto';
const MQTT_PORT = process.env.MQTT_PORT || 1883;
const CUMULUS_URL = process.env.CUMULUS_URL || 'http://cumulusmx:8998';
const POLL_INTERVAL_MS = (parseInt(process.env.SOLAR_POLL_INTERVAL_SEC, 10) || 600) * 1000;

// Esglaons d'alerta de consum alt (diferencial consum - generació)
const CONSUMPTION_THRESHOLDS = [500, 650, 800, 900, 1000, 1200, 1500, 1700, 2000];

// Finestra de monitoratge actiu: 07:00 a 21:00
function isMonitoringActive(date = new Date()) {
  const hour = date.getHours();
  return hour >= 7 && hour < 21;
}

// Botons interactius per al Panell en Directe
const liveDashboardButtons = {
  reply_markup: {
    inline_keyboard: [
      [
        { text: '🔄 Actualitzar Ara', callback_data: 'cmd_refresh_live' },
        { text: '🌤 Clima Davis', callback_data: 'cmd_clima' }
      ]
    ]
  }
};

// Botons interactius per a respostes sota demanda
const actionButtons = {
  reply_markup: {
    inline_keyboard: [
      [
        { text: '⚡ Estat Solar', callback_data: 'cmd_estat' },
        { text: '🌤 Clima Davis', callback_data: 'cmd_clima' }
      ]
    ]
  }
};

// Inicialització del Bot de Telegram
const bot = new TelegramBot(TELEGRAM_TOKEN, { polling: true });

bot.on('polling_error', (error) => {
  console.log('⚠️ Error de connexió amb Telegram:', error.code || error.message);
});

bot.on('message', (msg) => {
  if (msg.text) {
    console.log(`📩 [Telegram] Chat ID: ${msg.chat.id} (${msg.chat.title || 'Privat'}), Topic ID: ${msg.message_thread_id || 'General'}, Text: ${msg.text}`);
  }
});

// ------------------------------------------------------------------------------
// Client MQTT: Telemetria 24/7 cap a Mosquitto i recepció de dades Davis
// ------------------------------------------------------------------------------
console.log(`[MQTT] Conectant a mqtt://${MQTT_HOST}:${MQTT_PORT}...`);
const mqttClient = mqtt.connect(`mqtt://${MQTT_HOST}:${MQTT_PORT}`, {
  reconnectPeriod: 5000,
});

const weatherState = {
  temperatura: 'N/D',
  humedad: 'N/D',
  viento_velocidad: 'N/D',
  viento_direccion: 'N/D',
  lluvia_hoy: 'N/D',
  presion: 'N/D',
  radiacion_solar: null,
  uv: null,
  last_update: null
};

mqttClient.on('connect', () => {
  console.log('✅ [MQTT] Conectat correctament al broker Mosquitto.');
  mqttClient.subscribe(['clima/#'], (err) => {
    if (err) console.error('❌ [MQTT] Error en subscripció a clima/#:', err);
    else console.log('📡 [MQTT] Subscrit a canal meteorològic clima/#');
  });
});

mqttClient.on('message', (topic, message) => {
  const payload = message.toString();
  const subtopic = topic.split('/')[1];
  if (topic.startsWith('clima/') && subtopic) {
    weatherState[subtopic] = payload;
    weatherState.last_update = new Date();
  }
});

function publishSolarToMqtt(pvWatts, loadWatts, gridWatts) {
  if (!mqttClient.connected) return;
  try {
    mqttClient.publish('solar/power', pvWatts.toFixed(0), { retain: true });
    mqttClient.publish('solar/load', loadWatts.toFixed(0), { retain: true });
    mqttClient.publish('solar/grid', gridWatts.toFixed(0), { retain: true });
    mqttClient.publish('solar/status', JSON.stringify({
      power_w: Math.round(pvWatts),
      load_w: Math.round(loadWatts),
      grid_w: Math.round(gridWatts),
      timestamp: new Date().toISOString()
    }), { retain: true });
  } catch (err) {
    console.error('❌ [MQTT] Error publicant dades solars:', err.message);
  }
}

// ------------------------------------------------------------------------------
// Gestió de Memòria Diària Persistent
// ------------------------------------------------------------------------------
const MEMORY_FILE = './memoria.json';

function getTodayStr() {
  const d = new Date();
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

let state = {
  date: getTodayStr(),
  liveMessageId: null,
  summarySentDate: null,
  currentConsumptionTierIndex: -1,
  lastAlertMessageId: null,
  pvMax: 0,
  pvMaxTime: '--:--',
  pvSum: 0,
  pvCount: 0,
  loadMax: 0,
  loadMaxTime: '--:--',
  loadSum: 0,
  loadCount: 0,
  gridExportMax: 0,
  gridExportMaxTime: '--:--',
  gridImportMax: 0,
  gridImportMaxTime: '--:--',
  energyTodayKwh: 0
};

function loadMemory() {
  if (fs.existsSync(MEMORY_FILE)) {
    try {
      const data = JSON.parse(fs.readFileSync(MEMORY_FILE, 'utf8'));
      const today = getTodayStr();
      const isToday = data.date === today || data.date === new Date().getDate();
      if (isToday) {
        state = {
          ...state,
          ...data,
          date: today
        };
        if (state.dailyMax && (!state.pvMax || state.pvMax < state.dailyMax * 1000)) {
          state.pvMax = Math.round(state.dailyMax * 1000);
          if (!state.pvMaxTime || state.pvMaxTime === '--:--') state.pvMaxTime = '14:15';
        }
      } else {
        state.date = today;
      }
    } catch (e) {
      console.error('Error llegint memoria.json:', e.message);
    }
  }
}

function saveMemory() {
  try {
    fs.writeFileSync(MEMORY_FILE, JSON.stringify(state, null, 2));
  } catch (e) {
    console.error('Error desant memoria.json:', e.message);
  }
}

loadMemory();

// ------------------------------------------------------------------------------
// Consultes a Cumulus MX
// ------------------------------------------------------------------------------
async function fetchCumulusRaw() {
  try {
    const res = await axios.get(`${CUMULUS_URL}/api/data/currentdata`, { timeout: 4000 });
    return res.data || {};
  } catch (err) {
    return {};
  }
}

async function fetchWeatherFromCumulus() {
  const d = await fetchCumulusRaw();
  if (d && d.OutdoorTemp !== undefined && d.OutdoorTemp !== null) {
    if (mqttClient.connected) {
      mqttClient.publish('clima/temperatura', String(d.OutdoorTemp), { retain: true });
      mqttClient.publish('clima/humedad', String(d.OutdoorHum), { retain: true });
      mqttClient.publish('clima/presion', String(d.Pressure), { retain: true });
      mqttClient.publish('clima/viento_velocidad', String(d.WindAverage ?? d.WindLatest ?? 0), { retain: true });
      mqttClient.publish('clima/viento_direccion', String(d.Bearing ?? 0), { retain: true });
      mqttClient.publish('clima/lluvia_hoy', String(d.RainToday ?? 0), { retain: true });
      if (d.SolarRad !== undefined && d.SolarRad !== null) {
        mqttClient.publish('clima/radiacion_solar', String(d.SolarRad), { retain: true });
      }
      if (d.UVindex !== undefined && d.UVindex !== null) {
        mqttClient.publish('clima/uv', String(d.UVindex), { retain: true });
      }
    }
    weatherState.temperatura = d.OutdoorTemp;
    weatherState.humedad = d.OutdoorHum;
    weatherState.presion = d.Pressure;
    weatherState.viento_velocidad = d.WindAverage ?? d.WindLatest ?? 0;
    weatherState.viento_direccion = d.Bearing ?? 0;
    weatherState.lluvia_hoy = d.RainToday ?? 0;
    weatherState.radiacion_solar = d.SolarRad;
    weatherState.uv = d.UVindex;
    weatherState.last_update = new Date();
  }
}

// ------------------------------------------------------------------------------
// Estat del Cel (Càlcul radiació solar vs teòrica i llum diürna)
// ------------------------------------------------------------------------------
function getSkyCondition(solarRadVal, maxRadVal, sunriseStr, sunsetStr) {
  const now = new Date();
  const nowMins = now.getHours() * 60 + now.getMinutes();

  let riseMins = 7 * 60 + 30;
  let setMins = 20 * 60;
  if (sunriseStr && sunriseStr.includes(':')) {
    const [h, m] = sunriseStr.split(':').map(Number);
    riseMins = h * 60 + m;
  }
  if (sunsetStr && sunsetStr.includes(':')) {
    const [h, m] = sunsetStr.split(':').map(Number);
    setMins = h * 60 + m;
  }

  const isDaylight = nowMins >= riseMins && nowMins < setMins;
  const solarRad = parseFloat(solarRadVal) || 0;
  const maxRad = parseFloat(maxRadVal) || 0;

  if (!isDaylight || maxRad <= 25) {
    return {
      status: '🌙 Cel nocturn',
      desc: 'Sol post / Nit'
    };
  }

  if (maxRad <= 60) {
    return {
      status: '🌅 Alba / Crepuscle',
      desc: 'Llum crepuscular'
    };
  }

  const ratio = solarRad / maxRad;
  if (ratio >= 0.75) {
    return {
      status: '☀️ Cel serè',
      desc: 'Molt assolellat / Sense núvols'
    };
  } else if (ratio >= 0.50) {
    return {
      status: '🌤 Sol i núvols',
      desc: 'Parcialment cobert / Intervals de sol'
    };
  } else if (ratio >= 0.25) {
    return {
      status: '⛅ Bastant ennuvolat',
      desc: 'Cobert amb claraboies'
    };
  } else {
    return {
      status: '☁️ Cobert / Tapat',
      desc: 'Cel tapat per núvols densos'
    };
  }
}

// ------------------------------------------------------------------------------
// Consulta API SolarEdge (amb memòria cau per a overview)
// ------------------------------------------------------------------------------
async function fetchSolarEdgeData() {
  if (SOLAREDGE_API_KEY === 'pega_aqui_tu_api_key' || SOLAREDGE_API_KEY === 'DEMO' || !SOLAREDGE_API_KEY) {
    return {
      PV: { currentPower: 3.5 },
      LOAD: { currentPower: 1.2 },
      GRID: { currentPower: -2.3 },
      gridKwSigned: -2.3
    };
  }
  const url = `https://monitoringapi.solaredge.com/site/${SOLAREDGE_SITE_ID}/currentPowerFlow?api_key=${SOLAREDGE_API_KEY}`;
  const response = await axios.get(url, { timeout: 8000 });
  const flow = response.data.siteCurrentPowerFlow;
  
  const isExporting = flow.connections?.some(c => c.to?.toLowerCase() === 'grid');
  flow.gridKwSigned = isExporting ? -(flow.GRID?.currentPower || 0) : (flow.GRID?.currentPower || 0);
  
  return flow;
}

let cachedEnergyKwh = null;
let lastEnergyFetch = 0;

async function fetchSolarOverviewEnergy(force = false) {
  const now = Date.now();
  if (!force && cachedEnergyKwh !== null && (now - lastEnergyFetch < 20 * 60 * 1000)) {
    return cachedEnergyKwh;
  }
  if (!SOLAREDGE_API_KEY || SOLAREDGE_API_KEY === 'DEMO') {
    return 16.07;
  }
  try {
    const url = `https://monitoringapi.solaredge.com/site/${SOLAREDGE_SITE_ID}/overview?api_key=${SOLAREDGE_API_KEY}`;
    const res = await axios.get(url, { timeout: 8000 });
    const lastDayWh = res.data?.overview?.lastDayData?.energy;
    if (lastDayWh !== undefined && lastDayWh !== null) {
      cachedEnergyKwh = lastDayWh / 1000;
      lastEnergyFetch = now;
      return cachedEnergyKwh;
    }
  } catch (err) {
    console.error('Error consultant overview de SolarEdge:', err.message);
  }
  return cachedEnergyKwh || state.energyTodayKwh || 0;
}

// ------------------------------------------------------------------------------
// Estadístiques Diàries
// ------------------------------------------------------------------------------
function updateDailyStats(pvKw, loadKw, gridKw, energyKwh) {
  const timeStr = new Date().toLocaleTimeString('ca-ES', { hour: '2-digit', minute: '2-digit' });
  const pvW = pvKw * 1000;
  const loadW = loadKw * 1000;
  const gridW = gridKw * 1000;

  if (pvW > state.pvMax) {
    state.pvMax = pvW;
    state.pvMaxTime = timeStr;
  }
  if (pvW > 25) {
    state.pvSum += pvW;
    state.pvCount += 1;
  }

  if (loadW > state.loadMax) {
    state.loadMax = loadW;
    state.loadMaxTime = timeStr;
  }
  state.loadSum += loadW;
  state.loadCount += 1;

  if (gridW < -10) {
    const exportW = Math.abs(gridW);
    if (exportW > state.gridExportMax) {
      state.gridExportMax = exportW;
      state.gridExportMaxTime = timeStr;
    }
  } else if (gridW > 10) {
    if (gridW > state.gridImportMax) {
      state.gridImportMax = gridW;
      state.gridImportMaxTime = timeStr;
    }
  }

  if (energyKwh !== null && energyKwh !== undefined && energyKwh > 0) {
    state.energyTodayKwh = energyKwh;
  }
}

function checkDayRollover() {
  const today = getTodayStr();
  if (state.date !== today) {
    console.log(`🌅 [ROLLOVER] Canvi de dia detectat (${state.date} -> ${today}). Reiniciant mètriques diàries.`);
    state.date = today;
    state.liveMessageId = null;
    state.summarySentDate = null;
    state.currentConsumptionTierIndex = -1;
    state.lastAlertMessageId = null;
    state.pvMax = 0;
    state.pvMaxTime = '--:--';
    state.pvSum = 0;
    state.pvCount = 0;
    state.loadMax = 0;
    state.loadMaxTime = '--:--';
    state.loadSum = 0;
    state.loadCount = 0;
    state.gridExportMax = 0;
    state.gridExportMaxTime = '--:--';
    state.gridImportMax = 0;
    state.gridImportMaxTime = '--:--';
    state.energyTodayKwh = 0;
    cachedEnergyKwh = null;
    saveMemory();
  }
}

// ------------------------------------------------------------------------------
// Enviament i Gestió d'Alertes de Consum Alt (Auto-esborrat en resoldre/escalar)
// ------------------------------------------------------------------------------
async function sendConsumptionAlert(text) {
  const opts = { parse_mode: 'Markdown' };
  if (TELEGRAM_TOPIC_ID) opts.message_thread_id = TELEGRAM_TOPIC_ID;
  try {
    return await bot.sendMessage(CHAT_ID, text, opts);
  } catch (err) {
    console.error("Error enviant alerta:", err.message);
    return null;
  }
}

async function deleteLastAlert() {
  if (state.lastAlertMessageId) {
    try {
      await bot.deleteMessage(CHAT_ID, state.lastAlertMessageId);
      console.log(`🗑️ [ALERTA] Missatge d'alerta #${state.lastAlertMessageId} esborrat.`);
    } catch (err) {
      // Ignorar si ja l'havia esborrat l'usuari manualment
    }
    state.lastAlertMessageId = null;
    saveMemory();
  }
}

async function checkConsumptionAlerts(pvKw, loadKw) {
  // Només actiu durant la finestra de monitoratge (07:00 a 21:00)
  if (!isMonitoringActive()) return;

  const pvW = pvKw * 1000;
  const loadW = loadKw * 1000;
  // Diferència de consum per sobre de la generació solar
  const deficitW = Math.round(loadW - pvW);

  // Si el consum torna a estar per sota de 500W (resolt)
  if (deficitW < 500) {
    if (state.currentConsumptionTierIndex !== undefined && state.currentConsumptionTierIndex >= 0) {
      // Esborrem l'alerta de missatge anterior perquè ja està resolta
      await deleteLastAlert();
      state.currentConsumptionTierIndex = -1;
      saveMemory();
    }
    return;
  }

  // Determinar l'esglaó més alt superat
  let tierIndex = -1;
  for (let i = CONSUMPTION_THRESHOLDS.length - 1; i >= 0; i--) {
    if (deficitW >= CONSUMPTION_THRESHOLDS[i]) {
      tierIndex = i;
      break;
    }
  }

  const prevIndex = state.currentConsumptionTierIndex ?? -1;

  // Si ha pujat d'esglaó (escalada): esborrem l'anterior i enviem el nou esglaó
  if (tierIndex > prevIndex) {
    await deleteLastAlert();

    const threshold = CONSUMPTION_THRESHOLDS[tierIndex];
    const sent = await sendConsumptionAlert(
      `🚨 *Alerta de Consum Alt!*\n` +
      `S'estan gastant *${deficitW} W* més del que produeixen les plaques (esglaó: *${threshold} W*).\n\n` +
      `⚡ Producció solar: *${pvW.toFixed(0)} W*\n` +
      `🏠 Consum casa: *${loadW.toFixed(0)} W*\n` +
      `🔌 Comprant de la xarxa: *${deficitW} W*`
    );
    if (sent && sent.message_id) {
      state.lastAlertMessageId = sent.message_id;
    }
    state.currentConsumptionTierIndex = tierIndex;
    saveMemory();
  } else if (tierIndex < prevIndex) {
    // Si ha baixat a un esglaó inferior però encara > 500W, actualitzem esborrant l'anterior
    await deleteLastAlert();

    const threshold = CONSUMPTION_THRESHOLDS[tierIndex];
    const sent = await sendConsumptionAlert(
      `⚠️ *Consum en descens (encara alt):*\n` +
      `S'estan gastant *${deficitW} W* per sobre de la generació solar (esglaó: *${threshold} W*).\n\n` +
      `⚡ Solar: *${pvW.toFixed(0)} W* | 🏠 Consum: *${loadW.toFixed(0)} W*`
    );
    if (sent && sent.message_id) {
      state.lastAlertMessageId = sent.message_id;
    }
    state.currentConsumptionTierIndex = tierIndex;
    saveMemory();
  }
}

// ------------------------------------------------------------------------------
// Format del Panell Solar en Directe (Missatge Únic de 14 caràcters)
// ------------------------------------------------------------------------------
function formatLiveDashboard(pvKw, loadKw, gridKw, cumulusData, energyToday) {
  const pvW = Math.round(pvKw * 1000);
  const loadW = Math.round(loadKw * 1000);
  const gridW = Math.round(gridKw * 1000);

  let gridText = '';
  if (gridKw < -0.01) {
    gridText = `*${Math.abs(gridW)} W* (Venent excedent 📉)`;
  } else if (gridKw > 0.01) {
    gridText = `*${gridW} W* (Comprant de la xarxa 💸)`;
  } else {
    gridText = `*0 W* (Autosuficient 100% ⚖️)`;
  }

  const sky = getSkyCondition(cumulusData.SolarRad, cumulusData.CurrentSolarMax, cumulusData.Sunrise, cumulusData.Sunset);
  const timeStr = new Date().toLocaleTimeString('ca-ES', { hour: '2-digit', minute: '2-digit' });

  let text = `☀️ *PANELL SOLAR EN DIRECTE*\n`;
  text += `━━━━━━━━━━━━━━\n`;
  text += `⚡ *Producció solar:* *${pvW} W* (${pvKw.toFixed(2)} kW)\n`;
  text += `🏠 *Consum de casa:* *${loadW} W* (${loadKw.toFixed(2)} kW)\n`;
  text += `🔌 *Xarxa elèctrica:* ${gridText}\n\n`;

  text += `📊 *Balanç d'Avui:*\n`;
  if (energyToday !== null && energyToday !== undefined) {
    text += `• Energia generada avui: *${energyToday.toFixed(2)} kWh*\n`;
  }
  text += `• Pic solar màxim: *${state.pvMax.toFixed(0)} W* (${state.pvMaxTime})\n`;
  text += `• Consum màxim: *${state.loadMax.toFixed(0)} W* (${state.loadMaxTime})\n`;
  if (state.gridExportMax > 0) {
    text += `• Màx. excedent venut: *${state.gridExportMax.toFixed(0)} W* (${state.gridExportMaxTime})\n`;
  }
  text += `\n`;

  text += `🌤 *Estat del Cel i Meteorologia:*\n`;
  text += `• Cel: *${sky.status}* (${sky.desc})\n`;
  if (cumulusData.SolarRad !== undefined && cumulusData.SolarRad !== null) {
    text += `• Radiació solar: *${cumulusData.SolarRad} W/m²*`;
    if (cumulusData.HighSolarRadToday) {
      text += ` (Màx: ${cumulusData.HighSolarRadToday} W/m² a les ${cumulusData.HighSolarRadTodayTime || '--'})`;
    }
    text += `\n`;
  }
  if (cumulusData.SunshineHours !== undefined && cumulusData.SunshineHours !== null) {
    text += `• Hores de sol avui: *${cumulusData.SunshineHours} h*\n`;
  }
  text += `• 🌅 Sortida: *${cumulusData.Sunrise || '--:--'}* | 🌇 Posta: *${cumulusData.Sunset || '--:--'}*\n`;
  text += `━━━━━━━━━━━━━━\n`;
  text += `🔄 _Actualitzat a les ${timeStr} (En directe)_`;

  return text;
}

// ------------------------------------------------------------------------------
// Actualització del Panell en Directe
// ------------------------------------------------------------------------------
async function updateLiveDashboard(forceNew = false) {
  try {
    checkDayRollover();

    const flow = await fetchSolarEdgeData();
    if (!flow) return;

    const pvKw = flow.PV?.currentPower || 0;
    const loadKw = flow.LOAD?.currentPower || 0;
    const gridKw = flow.gridKwSigned !== undefined ? flow.gridKwSigned : (flow.GRID?.currentPower || 0);

    // Publicar a MQTT (ininterromput 24/7)
    publishSolarToMqtt(pvKw * 1000, loadKw * 1000, gridKw * 1000);

    // Si estem fora de l'horari actiu (21:00 a 07:00), aturem les edicions a Telegram
    if (!isMonitoringActive() && !forceNew) {
      return;
    }

    // Comprovació d'alertes de consum esglaonat (500W -> 2000W)
    await checkConsumptionAlerts(pvKw, loadKw);

    // Dades meteorològiques
    const cumulus = await fetchCumulusRaw();

    // Energia acumulada avui
    const energyKwh = await fetchSolarOverviewEnergy();

    // Actualitzar estadístiques del dia
    updateDailyStats(pvKw, loadKw, gridKw, energyKwh);

    // Format del text
    const text = formatLiveDashboard(pvKw, loadKw, gridKw, cumulus, energyKwh);

    const opts = {
      parse_mode: 'Markdown',
      ...liveDashboardButtons
    };
    if (TELEGRAM_TOPIC_ID) {
      opts.message_thread_id = TELEGRAM_TOPIC_ID;
    }

    if (state.liveMessageId && !forceNew) {
      try {
        await bot.editMessageText(text, {
          chat_id: CHAT_ID,
          message_id: state.liveMessageId,
          parse_mode: 'Markdown',
          ...liveDashboardButtons
        });
        saveMemory();
        return;
      } catch (err) {
        const desc = err.message || '';
        if (desc.includes('message is not modified')) {
          return;
        }
        console.log(`⚠️ [LIVE] Error editant missatge #${state.liveMessageId}: ${desc}. Enviant-ne un de nou...`);
      }
    }

    // Si no hi havia missatge previ o ha fallat l'edició, enviem-ne un de nou
    const sent = await bot.sendMessage(CHAT_ID, text, opts);
    state.liveMessageId = sent.message_id;
    saveMemory();
    console.log(`✨ [LIVE] Nou panell solar creat (#${sent.message_id}) al tema ${TELEGRAM_TOPIC_ID || 'general'}.`);

  } catch (error) {
    console.error('Error al actualitzar live dashboard:', error.message);
  }
}

// ------------------------------------------------------------------------------
// Resum Diari al Final del Dia (21:00h)
// ------------------------------------------------------------------------------
async function sendDailySummary() {
  try {
    await deleteLastAlert();

    const cumulus = await fetchCumulusRaw();
    const energyKwh = await fetchSolarOverviewEnergy(true);

    const avgPv = state.pvCount > 0 ? (state.pvSum / state.pvCount).toFixed(0) : 0;
    const avgLoad = state.loadCount > 0 ? (state.loadSum / state.loadCount).toFixed(0) : 0;

    const todayStr = new Date().toLocaleDateString('ca-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });

    let text = `📋 *RESUM DIARI DE GENERACIÓ SOLAR*\n`;
    text += `📅 Data: *${todayStr}*\n`;
    text += `━━━━━━━━━━━━━━\n`;
    text += `⚡ *Producció Solar:*\n`;
    text += `• Energia total generada: *${energyKwh ? energyKwh.toFixed(2) : (state.energyTodayKwh || 0).toFixed(2)} kWh*\n`;
    text += `• Pic màxim solar: *${state.pvMax.toFixed(0)} W* (${state.pvMaxTime})\n`;
    text += `• Mitjana diürna de producció: *${avgPv} W*\n\n`;

    text += `🏠 *Consum de la Llar:*\n`;
    text += `• Pic màxim de consum: *${state.loadMax.toFixed(0)} W* (${state.loadMaxTime})\n`;
    text += `• Consum mitjà: *${avgLoad} W*\n\n`;

    text += `🔌 *Balanç amb la Xarxa:*\n`;
    text += `• Pic màxim venut (excedent): *${state.gridExportMax.toFixed(0)} W* (${state.gridExportMaxTime})\n`;
    text += `• Pic màxim comprat: *${state.gridImportMax.toFixed(0)} W* (${state.gridImportMaxTime})\n\n`;

    text += `☀️ *Meteorologia Solar (Davis VP2):*\n`;
    text += `• Hores de sol efectives: *${cumulus.SunshineHours || '0.0'} h*\n`;
    text += `• Radiació solar màxima: *${cumulus.HighSolarRadToday || '--'} W/m²* (${cumulus.HighSolarRadTodayTime || '--'})\n`;
    text += `• 🌅 Sortida de sol: *${cumulus.Sunrise || '--:--'}*\n`;
    text += `• 🌇 Posta de sol: *${cumulus.Sunset || '--:--'}*\n`;
    text += `━━━━━━━━━━━━━━\n`;
    text += `🌙 _Monitoratge pausat fins a les 07:00 h. Bona nit!_`;

    const opts = { parse_mode: 'Markdown' };
    if (TELEGRAM_TOPIC_ID) opts.message_thread_id = TELEGRAM_TOPIC_ID;

    await bot.sendMessage(CHAT_ID, text, opts);
    console.log(`📊 [SUMMARY] Resum diari de generació enviat correctament a les 21:00h.`);

    // Esborra el panell viu que s'anava actualitzant cada 10 min per deixar el xat net amb només el resum
    if (state.liveMessageId) {
      try {
        await bot.deleteMessage(CHAT_ID, state.liveMessageId);
        console.log(`🧹 [LIVE] Panell viu #${state.liveMessageId} esborrat correctament per deixar el xat net.`);
      } catch (delErr) {
        console.warn(`⚠️ [LIVE] No s'ha pogut esborrar el panell viu #${state.liveMessageId}:`, delErr.message);
      }
      state.liveMessageId = null;
      saveMemory();
    }
  } catch (err) {
    console.error('Error enviant el resum diari:', err.message);
  }
}

async function checkDailySummary() {
  const now = new Date();
  const todayStr = getTodayStr();

  // Enviar a partir de les 21:00 h (final del monitoratge diari)
  const hour = now.getHours();

  if (hour >= 21 && state.summarySentDate !== todayStr) {
    await sendDailySummary();
    state.summarySentDate = todayStr;
    saveMemory();
  }
}

// ------------------------------------------------------------------------------
// Cicle Principal de Monitorització
// ------------------------------------------------------------------------------
async function mainCycle() {
  await updateLiveDashboard();
  await checkDailySummary();
}

// ------------------------------------------------------------------------------
// Comandes de Telegram sota Demanda
// ------------------------------------------------------------------------------
async function sendEstatMessage(chatId, threadId) {
  try {
    const data = await fetchSolarEdgeData();
    const pv = (data.PV?.currentPower || 0) * 1000;
    const load = (data.LOAD?.currentPower || 0) * 1000;
    const grid = (data.gridKwSigned !== undefined ? data.gridKwSigned : (data.GRID?.currentPower || 0)) * 1000;
    
    publishSolarToMqtt(pv, load, grid);

    let text = `☀️ *Estat actual de les plaques:*\n\n`;
    text += `⚡ Generació solar: *${pv.toFixed(0)} W*\n`;
    text += `🏠 Consum casa: *${load.toFixed(0)} W*\n`;
    text += `🔌 Xarxa elèctrica: *${Math.abs(grid).toFixed(0)} W* ${grid >= 0 ? '(Comprant 💸)' : '(Venent excedent 📉)'}\n`;
    text += `\n🕒 _Hora: ${new Date().toLocaleTimeString('ca-ES')}_`;
    
    const opts = { parse_mode: 'Markdown', ...actionButtons };
    if (threadId) opts.message_thread_id = threadId;
    bot.sendMessage(chatId, text, opts).catch(err => console.error(err));
  } catch (err) {
    const opts = { parse_mode: 'Markdown' };
    if (threadId) opts.message_thread_id = threadId;
    bot.sendMessage(chatId, `⚠️ Error en consultar l'API de SolarEdge: ${err.message}`, opts).catch(err => console.error(err));
  }
}

function sendClimaMessage(chatId, threadId) {
  const c = weatherState;
  const hora = c.last_update ? c.last_update.toLocaleTimeString('ca-ES') : 'Sense dades encara';

  let text = `🌤 *Estació Meteorològica Davis Vantage Pro2*\n\n`;
  text += `🌡 Temperatura: *${c.temperatura} °C*\n`;
  text += `💧 Humitat: *${c.humedad} %*\n`;
  text += `💨 Vent: *${c.viento_velocidad} km/h* (${c.viento_direccion}°)\n`;
  text += `🌧️ Pluja avui: *${c.lluvia_hoy} mm*\n`;
  text += `🧭 Pressió: *${c.presion} hPa*\n`;
  if (c.radiacion_solar) text += `☀️ Radiació solar: *${c.radiacion_solar} W/m²*\n`;
  if (c.uv) text += `🟣 Índex UV: *${c.uv}*\n`;
  text += `\n🕒 _Última actualització: ${hora}_`;

  const opts = { parse_mode: 'Markdown', ...actionButtons };
  if (threadId) opts.message_thread_id = threadId;
  bot.sendMessage(chatId, text, opts).catch(err => console.error(err));
}

bot.onText(/\/(estado|estat|solar)/, (msg) => {
  sendEstatMessage(msg.chat.id, msg.message_thread_id);
});

bot.onText(/\/(clima|temps|estacio)/, (msg) => {
  sendClimaMessage(msg.chat.id, msg.message_thread_id);
});

bot.onText(/\/(id|info)/, (msg) => {
  const chatId = msg.chat.id;
  const threadId = msg.message_thread_id;
  const opts = { parse_mode: 'Markdown' };
  if (threadId) opts.message_thread_id = threadId;
  const text = `ℹ️ *Identificadors d'aquest canal/tema:*\n\n` +
               `🔹 *Chat ID:* \`${chatId}\`\n` +
               `🔹 *Topic ID (Thread):* \`${threadId || 'General (Sense tema)'}\``;
  bot.sendMessage(chatId, text, opts).catch(err => console.error(err));
});

bot.onText(/\/(start|ajuda|help)/, (msg) => {
  const chatId = msg.chat.id;
  const threadId = msg.message_thread_id;
  const opts = { parse_mode: 'Markdown', ...actionButtons };
  if (threadId) opts.message_thread_id = threadId;
  const text = `👋 *Bot Homelab IoT (Solar & Meteorologia)*\n\n` +
               `Comandes disponibles:\n` +
               `☀️ /estat - Producció de plaques solars i consum de casa\n` +
               `🌤 /clima - Telemetria de l'estació Davis Vantage Pro2\n` +
               `🆔 /id - Conèixer l'ID d'aquest tema o grup\n` +
               `ℹ️ /ajuda - Mostrar aquesta llista de comandes`;
  bot.sendMessage(chatId, text, opts).catch(err => console.error(err));
});

// Gestor de botons interactius
bot.on('callback_query', async (query) => {
  const chatId = query.message.chat.id;
  const threadId = query.message.message_thread_id;
  const data = query.data;

  if (data === 'cmd_refresh_live') {
    bot.answerCallbackQuery(query.id, { text: '🔄 Actualitzant dades solars...' }).catch(() => {});
    await updateLiveDashboard(false);
  } else if (data === 'cmd_estat') {
    bot.answerCallbackQuery(query.id).catch(() => {});
    await sendEstatMessage(chatId, threadId);
  } else if (data === 'cmd_clima') {
    bot.answerCallbackQuery(query.id).catch(() => {});
    sendClimaMessage(chatId, threadId);
  }
});

// ------------------------------------------------------------------------------
// Inicialització
// ------------------------------------------------------------------------------
if (!TELEGRAM_TOKEN || !CHAT_ID) {
  console.log('⚠️ Falten dades vitals (Token o Chat ID) a l\'arxiu .env.');
} else {
  console.log(`🤖 Bot de SolarEdge iniciat. Horari actiu: 07:00h a 21:00h. Cicle cada ${POLL_INTERVAL_MS / 60000} min.`);

  mainCycle();
  setInterval(mainCycle, POLL_INTERVAL_MS);

  fetchWeatherFromCumulus();
  setInterval(fetchWeatherFromCumulus, 30000);
}

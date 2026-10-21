require('dotenv').config();
const TelegramBot = require('node-telegram-bot-api').default || require('node-telegram-bot-api');
const axios = require('axios');
const fs = require('fs');
const mqtt = require('mqtt');
const net = require('net');

// Variables d'entorn
const TELEGRAM_TOKEN = process.env.TELEGRAM_TOKEN || process.env.TELEGRAM_BOT_TOKEN;
const CHAT_ID = process.env.CHAT_ID || process.env.TELEGRAM_CHAT_ID;
const TELEGRAM_TOPIC_ID = process.env.TELEGRAM_TOPIC_ID ? parseInt(process.env.TELEGRAM_TOPIC_ID, 10) : null;
const SOLAREDGE_SITE_ID = process.env.SOLAREDGE_SITE_ID;
const SOLAREDGE_API_KEY = process.env.SOLAREDGE_API_KEY;

// Configuració Modbus TCP Local (Inversor SolarEdge)
const MODBUS_ENABLED = process.env.MODBUS_ENABLED !== 'false';
const MODBUS_HOST = process.env.MODBUS_INVERTER_IP || '192.168.2.213';
const MODBUS_PORT = parseInt(process.env.MODBUS_INVERTER_PORT, 10) || 1502;
const MODBUS_UNIT_ID = parseInt(process.env.MODBUS_UNIT_ID, 10) || 2;
const MODBUS_TIMEOUT_MS = parseInt(process.env.MODBUS_TIMEOUT_MS, 10) || 4000;

const MQTT_HOST = process.env.MQTT_BROKER_HOST || 'mosquitto';
const MQTT_PORT = process.env.MQTT_PORT || 1883;
const CUMULUS_URL = process.env.CUMULUS_URL || 'http://cumulusmx:8998';
const POLL_INTERVAL_MS = (parseInt(process.env.SOLAR_POLL_INTERVAL_SEC, 10) || 60) * 1000;

// Esglaons d'alerta de consum alt (diferencial consum - generació)
const CONSUMPTION_THRESHOLDS = [500, 650, 800, 900, 1000, 1200, 1500, 1700, 2000];

// ------------------------------------------------------------------------------
// Finestra de Monitoratge Actiu Dinàmic (Adaptat a les Hores de Llum Solar)
// ------------------------------------------------------------------------------
const SALLENT_LAT = 41.8219;
const SALLENT_LON = 1.8953;

let lastCumulusData = null;

// Càlcul astronòmic autònom de sortida i posta de sol (NOAA Solar Algorithm) per a Sallent
function calculateSolarTimes(date = new Date(), lat = SALLENT_LAT, lon = SALLENT_LON) {
  const rad = Math.PI / 180;
  const deg = 180 / Math.PI;

  const startOfYear = new Date(date.getFullYear(), 0, 0);
  const diff = date - startOfYear;
  const dayOfYear = Math.floor(diff / (1000 * 60 * 60 * 24));

  const gamma = 2 * Math.PI / 365 * (dayOfYear - 1 + (date.getHours() - 12) / 24);

  const eqtime = 229.18 * (
    0.000075 + 0.001868 * Math.cos(gamma) - 0.032077 * Math.sin(gamma)
    - 0.014615 * Math.cos(2 * gamma) - 0.040849 * Math.sin(2 * gamma)
  );

  const decl = 0.006918 - 0.399912 * Math.cos(gamma) + 0.070257 * Math.sin(gamma)
    - 0.006758 * Math.cos(2 * gamma) + 0.000907 * Math.sin(2 * gamma)
    - 0.002697 * Math.cos(3 * gamma) + 0.0148 * Math.sin(3 * gamma);

  const zenithRad = 90.833 * rad; // Zenith estàndard per refracció atmosfèrica
  const latRad = lat * rad;
  const cosHA = (Math.cos(zenithRad) - Math.sin(latRad) * Math.sin(decl)) / (Math.cos(latRad) * Math.cos(decl));

  if (cosHA > 1 || cosHA < -1) return null;

  const haDeg = Math.acos(cosHA) * deg;

  const solarNoonUTC = 720 - 4 * lon - eqtime;
  const sunriseUTC = solarNoonUTC - 4 * haDeg;
  const sunsetUTC = solarNoonUTC + 4 * haDeg;

  const tzOffsetMins = -date.getTimezoneOffset();
  const sunriseLocalMins = Math.round(sunriseUTC + tzOffsetMins);
  const sunsetLocalMins = Math.round(sunsetUTC + tzOffsetMins);

  const formatTime = (mins) => {
    const h = Math.floor(mins / 60) % 24;
    const m = mins % 60;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
  };

  return {
    sunriseMins: sunriseLocalMins,
    sunsetMins: sunsetLocalMins,
    sunriseStr: formatTime(sunriseLocalMins),
    sunsetStr: formatTime(sunsetLocalMins)
  };
}

// Determina la finestra de llum solar del dia (Cumulus MX -> Càlcul NOAA -> Taula mensual de seguretat)
function getDaylightWindow(date = new Date()) {
  let sunriseStr = null;
  let sunsetStr = null;
  let sunriseMins = null;
  let sunsetMins = null;

  // 1. Prioritat: dades en directe de Cumulus MX (calculades per la pròpia estació Davis a Sallent)
  if (lastCumulusData) {
    if (lastCumulusData.Sunrise && typeof lastCumulusData.Sunrise === 'string' && lastCumulusData.Sunrise.includes(':')) {
      const s = lastCumulusData.Sunrise.trim();
      const [h, m] = s.split(':').map(Number);
      if (!isNaN(h) && !isNaN(m)) {
        sunriseStr = s;
        sunriseMins = h * 60 + m;
      }
    }
    if (lastCumulusData.Sunset && typeof lastCumulusData.Sunset === 'string' && lastCumulusData.Sunset.includes(':')) {
      const s = lastCumulusData.Sunset.trim();
      const [h, m] = s.split(':').map(Number);
      if (!isNaN(h) && !isNaN(m)) {
        sunsetStr = s;
        sunsetMins = h * 60 + m;
      }
    }
  }

  // 2. Si Cumulus encara no ha respost, càlcul astronòmic d'alta precisió per Sallent
  if (sunriseMins === null || sunsetMins === null) {
    const astro = calculateSolarTimes(date);
    if (astro) {
      if (sunriseMins === null) {
        sunriseMins = astro.sunriseMins;
        sunriseStr = astro.sunriseStr;
      }
      if (sunsetMins === null) {
        sunsetMins = astro.sunsetMins;
        sunsetStr = astro.sunsetStr;
      }
    }
  }

  // 3. Fallback mensual fix (seguretat absoluta si qualsevol altre mètode fallés)
  if (sunriseMins === null || sunsetMins === null) {
    const month = date.getMonth();
    const monthlyFallbacks = [
      { rise: '08:15', set: '17:45' }, // Gen
      { rise: '07:50', set: '18:25' }, // Feb
      { rise: '07:20', set: '19:15' }, // Mar
      { rise: '07:05', set: '20:35' }, // Abr
      { rise: '06:30', set: '21:10' }, // Mai
      { rise: '06:15', set: '21:35' }, // Jun
      { rise: '06:25', set: '21:30' }, // Jul
      { rise: '07:00', set: '21:00' }, // Ago
      { rise: '07:35', set: '20:10' }, // Set
      { rise: '08:05', set: '19:15' }, // Oct
      { rise: '07:45', set: '17:35' }, // Nov
      { rise: '08:15', set: '17:25' }  // Des
    ];
    const fb = monthlyFallbacks[month];
    sunriseStr = fb.rise;
    sunsetStr = fb.set;
    const [rh, rm] = sunriseStr.split(':').map(Number);
    const [sh, sm] = sunsetStr.split(':').map(Number);
    sunriseMins = rh * 60 + rm;
    sunsetMins = sh * 60 + sm;
  }

  // Marge actiu adaptat a la generació solar:
  // - Comença 15 min abans de la sortida de sol (albada, primera llum difusa)
  // - Acaba 20 min després de la posta de sol (crepuscle, quan la producció solar cau a 0 W)
  const startMins = Math.max(0, sunriseMins - 15);
  const endMins = Math.min(24 * 60 - 1, sunsetMins + 20);

  const formatTime = (mins) => {
    const h = Math.floor(mins / 60) % 24;
    const m = mins % 60;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
  };

  return {
    sunriseStr,
    sunsetStr,
    sunriseMins,
    sunsetMins,
    startMins,
    endMins,
    startStr: formatTime(startMins),
    endStr: formatTime(endMins)
  };
}

// Finestra de monitoratge actiu dinàmic segons hores de llum solar
function isMonitoringActive(date = new Date()) {
  const nowMins = date.getHours() * 60 + date.getMinutes();
  const w = getDaylightWindow(date);
  return nowMins >= w.startMins && nowMins < w.endMins;
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

function publishSolarToMqtt(pvWatts, loadWatts, gridWatts, energyTodayKwh) {
  if (!mqttClient.connected) return;
  try {
    mqttClient.publish('solar/power', pvWatts.toFixed(0), { retain: true });
    mqttClient.publish('solar/load', loadWatts.toFixed(0), { retain: true });
    mqttClient.publish('solar/grid', gridWatts.toFixed(0), { retain: true });
    if (energyTodayKwh !== undefined && energyTodayKwh !== null && !isNaN(energyTodayKwh)) {
      mqttClient.publish('solar/energy_today', Number(energyTodayKwh).toFixed(2), { retain: true });
    }
    mqttClient.publish('solar/status', JSON.stringify({
      power_w: Math.round(pvWatts),
      load_w: Math.round(loadWatts),
      grid_w: Math.round(gridWatts),
      energy_today_kwh: (energyTodayKwh !== undefined && energyTodayKwh !== null && !isNaN(energyTodayKwh)) ? Number(Number(energyTodayKwh).toFixed(2)) : undefined,
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
  energyTodayKwh: 0,
  midnightLifetimeKwh: 0,
  lastLifetimeKwh: 0
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
    const data = res.data || {};
    if (data && Object.keys(data).length > 0) {
      lastCumulusData = data;
    }
    return data;
  } catch (err) {
    return lastCumulusData || {};
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
// Consulta Modbus TCP Local (i fallback a Cloud API)
// ------------------------------------------------------------------------------
function readModbusRegisters(ip, port, unitId, startAddr, regCount, timeoutMs = MODBUS_TIMEOUT_MS) {
  return new Promise((resolve, reject) => {
    const socket = new net.Socket();
    let isDone = false;
    let timer = setTimeout(() => {
      if (!isDone) {
        isDone = true;
        socket.destroy();
        reject(new Error(`Modbus TCP Timeout (${timeoutMs}ms)`));
      }
    }, timeoutMs);

    const txId = Math.floor(Math.random() * 65535);
    const req = Buffer.alloc(12);
    req.writeUInt16BE(txId, 0);      // Transaction ID
    req.writeUInt16BE(0, 2);         // Protocol ID (0 = Modbus TCP)
    req.writeUInt16BE(6, 4);         // Longitud (6 bytes següents)
    req.writeUInt8(unitId, 6);       // Unit ID (2)
    req.writeUInt8(3, 7);            // Function code 3 (Read Holding Registers)
    req.writeUInt16BE(startAddr, 8);  // Adreça registre inici
    req.writeUInt16BE(regCount, 10);  // Quantitat de registres

    let chunks = [];

    socket.connect(port, ip, () => {
      socket.write(req);
    });

    socket.on('data', (data) => {
      chunks.push(data);
      const total = Buffer.concat(chunks);
      if (total.length >= 9) {
        const byteCount = total.readUInt8(8);
        if (total.length >= 9 + byteCount) {
          if (!isDone) {
            isDone = true;
            clearTimeout(timer);
            socket.end();
            const pdu = total.slice(9, 9 + byteCount);
            resolve(pdu);
          }
        }
      }
    });

    socket.on('error', (err) => {
      if (!isDone) {
        isDone = true;
        clearTimeout(timer);
        socket.destroy();
        reject(err);
      }
    });

    socket.on('close', () => {
      if (!isDone) {
        isDone = true;
        clearTimeout(timer);
        reject(new Error('Modbus socket tancat prematurament'));
      }
    });
  });
}

async function fetchSolarEdgeDataModbus() {
  // Model SunSpec 101/103 (Inversor SolarEdge): registre 40069, 40 registres
  const inv = await readModbusRegisters(MODBUS_HOST, MODBUS_PORT, MODBUS_UNIT_ID, 40069, 40);
  const pvRaw = inv.readInt16BE(14 * 2);
  const pvSf = inv.readInt16BE(15 * 2);
  const pvWatts = Math.max(0, pvRaw * Math.pow(10, pvSf));

  const lifeRaw = inv.readUInt32BE(24 * 2);
  const lifeSf = inv.readInt16BE(26 * 2);
  const lifetimeKwh = (lifeRaw * Math.pow(10, lifeSf)) / 1000;
  const status = inv.readUInt16BE(38 * 2);

  state.lastLifetimeKwh = lifetimeKwh;
  if (!state.midnightLifetimeKwh || state.midnightLifetimeKwh <= 0) {
    state.midnightLifetimeKwh = Math.max(0, lifetimeKwh - (state.energyTodayKwh || 0));
  }
  const energyTodayKwh = Math.max(0, lifetimeKwh - state.midnightLifetimeKwh);
  state.energyTodayKwh = Math.round(energyTodayKwh * 1000) / 1000;

  // Pausa de 120ms abans de consultar el comptador (l'inversor només accepta 1 connexió Modbus concurrent)
  await new Promise(r => setTimeout(r, 120));

  let gridWatts = 0;
  try {
    // Model SunSpec 203 (Comptador d'energia xarxa): registre 40188, 30 registres
    const meter = await readModbusRegisters(MODBUS_HOST, MODBUS_PORT, MODBUS_UNIT_ID, 40188, 30);
    const gridRaw = meter.readInt16BE(18 * 2);
    const gridSf = meter.readInt16BE(22 * 2);
    const rawGridWatts = gridRaw * Math.pow(10, gridSf);
    // En aquesta instal·lació física (orientació pinces TI / comptador), el registre
    // és positiu en injectar excedent a la xarxa i negatiu en consumir de la xarxa.
    // Invertim el signe per al conveni del bot (+ comprant de xarxa, - venent excedent):
    gridWatts = -rawGridWatts;
  } catch (meterErr) {
    console.warn('⚠️ [Modbus] Error llegint comptador (Model 203):', meterErr.message);
  }

  // Conservació d'energia: Consum casa = Producció solar + Balanç de xarxa (+ consum xarxa, - injecció excedent)
  const loadWatts = Math.max(0, pvWatts + gridWatts);

  const pvKw = pvWatts / 1000;
  const loadKw = loadWatts / 1000;
  const gridKw = gridWatts / 1000;

  return {
    source: 'modbus',
    PV: { currentPower: pvKw },
    LOAD: { currentPower: loadKw },
    GRID: { currentPower: Math.abs(gridKw) },
    gridKwSigned: gridKw,
    inverterStatus: status,
    lifetimeKwh,
    energyTodayKwh: state.energyTodayKwh
  };
}

async function fetchSolarEdgeDataCloud() {
  if (SOLAREDGE_API_KEY === 'pega_aqui_tu_api_key' || SOLAREDGE_API_KEY === 'DEMO' || !SOLAREDGE_API_KEY) {
    return {
      source: 'demo',
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
  flow.source = 'cloud';
  
  return flow;
}

async function fetchSolarEdgeData() {
  if (MODBUS_ENABLED) {
    try {
      const data = await fetchSolarEdgeDataModbus();
      return data;
    } catch (err) {
      console.warn(`⚠️ [Modbus] Error lectura local (${err.message}). Reintentant amb Cloud API...`);
    }
  }
  return await fetchSolarEdgeDataCloud();
}

let cachedEnergyKwh = null;
let lastEnergyFetch = 0;

async function fetchSolarOverviewEnergy(force = false) {
  if (MODBUS_ENABLED && state.energyTodayKwh !== undefined && state.energyTodayKwh !== null && state.energyTodayKwh > 0) {
    return state.energyTodayKwh;
  }
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
    console.error('Error consultant overview de SolarEdge Cloud:', err.message);
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
    if (state.lastLifetimeKwh) {
      state.midnightLifetimeKwh = state.lastLifetimeKwh;
    }
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
    // Si ha baixat d'esglaó però encara > 500W, editem el missatge existent sense generar noves notificacions sonores
    const threshold = CONSUMPTION_THRESHOLDS[tierIndex];
    const text = `⚠️ *Consum en descens (encara alt):*\n` +
      `S'estan gastant *${deficitW} W* per sobre de la generació solar (esglaó: *${threshold} W*).\n\n` +
      `⚡ Solar: *${pvW.toFixed(0)} W* | 🏠 Consum: *${loadW.toFixed(0)} W*`;

    if (state.lastAlertMessageId) {
      try {
        await bot.editMessageText(text, {
          chat_id: CHAT_ID,
          message_id: state.lastAlertMessageId,
          parse_mode: 'Markdown'
        });
      } catch (err) {
        await deleteLastAlert();
        const sent = await sendConsumptionAlert(text);
        if (sent && sent.message_id) {
          state.lastAlertMessageId = sent.message_id;
        }
      }
    } else {
      const sent = await sendConsumptionAlert(text);
      if (sent && sent.message_id) {
        state.lastAlertMessageId = sent.message_id;
      }
    }
    state.currentConsumptionTierIndex = tierIndex;
    saveMemory();
  }
}

function formatLiveDashboard(pvKw, loadKw, gridKw, cumulusData, energyToday, source = 'modbus') {
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
  const sourceLabel = source === 'modbus' ? 'Modbus TCP Local ⚡' : 'SolarEdge Cloud ☁️';

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
  text += `🔄 _Actualitzat a les ${timeStr} (${sourceLabel})_`;

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

    // Energia acumulada avui (de Modbus o fallback a Cloud overview)
    const energyKwh = flow.energyTodayKwh !== undefined ? flow.energyTodayKwh : await fetchSolarOverviewEnergy();

    // Publicar a MQTT (ininterromput 24/7, incloent energia d'avui)
    publishSolarToMqtt(pvKw * 1000, loadKw * 1000, gridKw * 1000, energyKwh);

    // Si estem fora de l'horari actiu (21:00 a 07:00), aturem les edicions a Telegram
    if (!isMonitoringActive() && !forceNew) {
      return;
    }

    // Comprovació d'alertes de consum esglaonat (500W -> 2000W)
    await checkConsumptionAlerts(pvKw, loadKw);

    // Dades meteorològiques
    const cumulus = await fetchCumulusRaw();

    // Actualitzar estadístiques del dia
    updateDailyStats(pvKw, loadKw, gridKw, energyKwh);

    // Format del text
    const text = formatLiveDashboard(pvKw, loadKw, gridKw, cumulus, energyKwh, flow.source);

    const opts = {
      parse_mode: 'Markdown',
      ...liveDashboardButtons
    };
    if (TELEGRAM_TOPIC_ID) {
      opts.message_thread_id = TELEGRAM_TOPIC_ID;
    }

    if (forceNew && state.liveMessageId) {
      try {
        await bot.deleteMessage(CHAT_ID, state.liveMessageId);
        console.log(`🧹 [LIVE] Panell viu #${state.liveMessageId} esborrat per forceNew.`);
      } catch (delErr) {
        // Ignorar si ja no existia
      }
      state.liveMessageId = null;
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
        console.log(`🔄 [LIVE] Panell actualitzat (#${state.liveMessageId}) a les ${new Date().toLocaleTimeString('ca-ES')} (PV: ${(pvKw*1000).toFixed(0)}W, Casa: ${(loadKw*1000).toFixed(0)}W, Xarxa: ${(gridKw*1000).toFixed(0)}W).`);
        return;
      } catch (err) {
        const desc = err.message || '';
        if (desc.includes('message is not modified')) {
          return;
        }

        // Si és un error temporal de xarxa o timeout de Telegram (EFATAL, fetch failed, timeout, 429...),
        // el missatge original encara existeix a Telegram. NO n'hem d'enviar cap de nou per evitar duplicats!
        const isNetworkError = /EFATAL|fetch failed|ETIMEDOUT|ECONNRESET|ECONNREFUSED|ENOTFOUND|socket hang up|timeout|network|429/i.test(desc);
        if (isNetworkError) {
          console.warn(`⚠️ [LIVE] Error temporal de xarxa en editar el missatge #${state.liveMessageId} (${desc}). Es manté el panell i es reintentarà al proper cicle.`);
          return;
        }

        console.log(`⚠️ [LIVE] Error de Telegram editant missatge #${state.liveMessageId}: ${desc}. Netejant l'antic i creant-ne un de nou...`);
        try {
          await bot.deleteMessage(CHAT_ID, state.liveMessageId);
        } catch (delErr) {
          // Ignorar si ja no existia
        }
        state.liveMessageId = null;
      }
    }

    // Si no hi havia missatge previ o Telegram ha confirmat que no es pot editar, enviem-ne un de nou
    const sent = await bot.sendMessage(CHAT_ID, text, opts);
    state.liveMessageId = sent.message_id;
    saveMemory();
    console.log(`✨ [LIVE] Nou panell solar creat (#${sent.message_id}) al tema ${TELEGRAM_TOPIC_ID || 'general'}.`);

  } catch (error) {
    console.error('Error al actualitzar live dashboard:', error.message);
  }
}

// ------------------------------------------------------------------------------
// Resum Diari al Final del Dia (Posta de Sol)
// ------------------------------------------------------------------------------
async function sendDailySummary(daylightWindow) {
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
    text += `• 🌅 Sortida de sol: *${cumulus.Sunrise || daylightWindow?.sunriseStr || '--:--'}*\n`;
    text += `• 🌇 Posta de sol: *${cumulus.Sunset || daylightWindow?.sunsetStr || '--:--'}*\n`;
    text += `━━━━━━━━━━━━━━\n`;

    // Càlcul de la sortida de sol de demà per a la informació de repòs nocturn
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const tomorrowWindow = getDaylightWindow(tomorrow);
    const nextSunrise = tomorrowWindow ? tomorrowWindow.sunriseStr : (daylightWindow ? daylightWindow.sunriseStr : '07:30');

    text += `🌙 _Posta de sol completada. Monitoratge pausat fins a la sortida de sol (~${nextSunrise} h). Bona nit!_`;

    const opts = { parse_mode: 'Markdown' };
    if (TELEGRAM_TOPIC_ID) opts.message_thread_id = TELEGRAM_TOPIC_ID;

    await bot.sendMessage(CHAT_ID, text, opts);
    console.log(`📊 [SUMMARY] Resum diari de generació enviat correctament després de la posta de sol (${daylightWindow?.sunsetStr || 'vespre'}).`);

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
  const nowMins = now.getHours() * 60 + now.getMinutes();
  const daylightWindow = getDaylightWindow(now);

  // Enviar un cop superat el final de la finestra de llum solar (posta de sol + marge de crepuscle)
  if (nowMins >= daylightWindow.endMins && state.summarySentDate !== todayStr) {
    await sendDailySummary(daylightWindow);
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
    const energyToday = data.energyTodayKwh !== undefined ? data.energyTodayKwh : state.energyTodayKwh;
    
    publishSolarToMqtt(pv, load, grid, energyToday);

    const sourceLabel = data.source === 'modbus' ? 'Modbus TCP Local ⚡' : 'SolarEdge Cloud ☁️';

    let text = `☀️ *Estat actual de les plaques:*\n\n`;
    text += `⚡ Generació solar: *${pv.toFixed(0)} W*\n`;
    text += `🏠 Consum casa: *${load.toFixed(0)} W*\n`;
    text += `🔌 Xarxa elèctrica: *${Math.abs(grid).toFixed(0)} W* ${grid >= 0 ? '(Comprant 💸)' : '(Venent excedent 📉)'}\n`;
    if (energyToday !== undefined && energyToday !== null) {
      text += `📊 Energia generada avui: *${Number(energyToday).toFixed(2)} kWh*\n`;
    }
    text += `\n🕒 _Hora: ${new Date().toLocaleTimeString('ca-ES')} (${sourceLabel})_`;
    
    const opts = { parse_mode: 'Markdown', ...actionButtons };
    if (threadId) opts.message_thread_id = threadId;
    bot.sendMessage(chatId, text, opts).catch(err => console.error(err));
  } catch (err) {
    const opts = { parse_mode: 'Markdown' };
    if (threadId) opts.message_thread_id = threadId;
    bot.sendMessage(chatId, `⚠️ Error en consultar telemetria solar: ${err.message}`, opts).catch(err => console.error(err));
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

bot.onText(/\/testalerta/, async (msg) => {
  const chatId = msg.chat.id;
  const threadId = msg.message_thread_id;
  const opts = { parse_mode: 'Markdown' };
  if (threadId) opts.message_thread_id = threadId;

  console.log(`🧪 [TEST] Llançant alerta de consum alt de prova per petició de l'usuari.`);
  const alertSent = await bot.sendMessage(
    chatId,
    `🚨 *[PROVA] Alerta de Consum Alt Simulat!*\n` +
    `S'estan gastant *1.250 W* més del que produeixen les plaques (esglaó: *1.200 W*).\n\n` +
    `⚡ Producció solar: *180 W*\n` +
    `🏠 Consum casa: *1.430 W*\n` +
    `🔌 Comprant de la xarxa: *1.250 W*\n\n` +
    `⏱ _Aquest missatge de prova s'auto-esborrarà d'aquí a 15 segons per simular la normalització del consum._`,
    opts
  );

  setTimeout(async () => {
    try {
      if (alertSent && alertSent.message_id) {
        await bot.deleteMessage(chatId, alertSent.message_id);
        console.log(`🧹 [TEST] Alerta de prova esborrada.`);
        const resSent = await bot.sendMessage(chatId, `✅ *[PROVA] Consum normalitzat!* L'alerta anterior s'ha auto-esborrat correctament per mantenir el xat net.`, opts);
        setTimeout(() => bot.deleteMessage(chatId, resSent.message_id).catch(() => {}), 10000);
      }
    } catch (e) {}
  }, 15000);
});

bot.onText(/\/testresum/, async (msg) => {
  console.log(`🧪 [TEST] Llançant resum diari de prova.`);
  const w = getDaylightWindow();
  await sendDailySummary(w);
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
  const todayWindow = getDaylightWindow();
  console.log(`🤖 Bot de SolarEdge iniciat. Horari dinàmic de llum: ${todayWindow.sunriseStr}h a ${todayWindow.sunsetStr}h (monitoratge actiu: ${todayWindow.startStr}h a ${todayWindow.endStr}h). Cicle cada ${POLL_INTERVAL_MS / 60000} min.`);

  mainCycle();
  setInterval(mainCycle, POLL_INTERVAL_MS);

  fetchWeatherFromCumulus();
  setInterval(fetchWeatherFromCumulus, 30000);
}

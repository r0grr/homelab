const mqtt = require('mqtt');
const { Telegraf } = require('telegraf');

const BOT_TOKEN = process.env.TELEGRAM_BOT_TOKEN;
const MQTT_HOST = process.env.MQTT_BROKER_HOST || 'mosquitto';
const MQTT_PORT = process.env.MQTT_PORT || 1883;

// Almacén de último estado recibido por MQTT
const state = {
  clima: {
    temperatura: 'N/D',
    humedad: 'N/D',
    viento_velocidad: 'N/D',
    viento_direccion: 'N/D',
    lluvia_hoy: 'N/D',
    presion: 'N/D',
    last_update: null
  },
  solar: {
    power: 'N/D',
    energy_today: 'N/D',
    last_update: null
  }
};

// Conexión MQTT
console.log(`[TelegramBot] Conectando a MQTT: mqtt://${MQTT_HOST}:${MQTT_PORT}...`);
const mqttClient = mqtt.connect(`mqtt://${MQTT_HOST}:${MQTT_PORT}`, {
  reconnectPeriod: 5000
});

mqttClient.on('connect', () => {
  console.log('[TelegramBot] Conectado a MQTT. Suscribiendo a temas...');
  mqttClient.subscribe(['clima/#', 'solar/#'], (err) => {
    if (err) console.error('[TelegramBot] Error en suscripción MQTT:', err);
    else console.log('[TelegramBot] Suscrito con éxito a clima/# y solar/#');
  });
});

mqttClient.on('message', (topic, message) => {
  const payload = message.toString();
  const subtopic = topic.split('/')[1];

  if (topic.startsWith('clima/')) {
    if (subtopic) state.clima[subtopic] = payload;
    state.clima.last_update = new Date();
  } else if (topic.startsWith('solar/')) {
    if (subtopic) state.solar[subtopic] = payload;
    state.solar.last_update = new Date();
  }
});

// Inicialización de Telegraf
if (!BOT_TOKEN || BOT_TOKEN === 'pon_aqui_tu_token_de_telegram') {
  console.warn('[TelegramBot] AVISO: TELEGRAM_BOT_TOKEN no configurado en .env. El bot permanecerá en espera.');
} else {
  const bot = new Telegraf(BOT_TOKEN);

  bot.start((ctx) => {
    ctx.reply(
      '👋 ¡Hola! Soy el bot de tu servidor Homelab.\n\n' +
      'Comandos disponibles:\n' +
      '🌤 /clima - Telemetría actual de la estación Davis Vantage Pro2\n' +
      '☀️ /solar - Producción actual de las placas solares\n' +
      '📊 /estado - Resumen general del sistema'
    );
  });

  bot.command('clima', (ctx) => {
    const c = state.clima;
    ctx.reply(
      `🌡 *Estación Davis Vantage Pro2*\n` +
      `────────────────────────────\n` +
      `• Temperatura: *${c.temperatura} °C*\n` +
      `• Humedad: *${c.humedad} %*\n` +
      `• Viento: *${c.viento_velocidad} km/h* (${c.viento_direccion}°)\n` +
      `• Lluvia hoy: *${c.lluvia_hoy} mm*\n` +
      `• Presión: *${c.presion} hPa*\n` +
      `🕒 _Última actualización: ${c.last_update ? c.last_update.toLocaleTimeString('es-ES') : 'Sin datos'}_`,
      { parse_mode: 'Markdown' }
    );
  });

  bot.command('solar', (ctx) => {
    const s = state.solar;
    ctx.reply(
      `☀️ *Generación Solar Fotovoltaica*\n` +
      `────────────────────────────\n` +
      `• Potencia instantánea: *${s.power} W*\n` +
      `• Generado hoy: *${s.energy_today} kWh*\n` +
      `🕒 _Última actualización: ${s.last_update ? s.last_update.toLocaleTimeString('es-ES') : 'Sin datos'}_`,
      { parse_mode: 'Markdown' }
    );
  });

  bot.command('estado', (ctx) => {
    ctx.reply(
      `📊 *Resumen Homelab IoT*\n` +
      `────────────────────────────\n` +
      `🌡 Clima: *${state.clima.temperatura} °C* | *${state.clima.viento_velocidad} km/h*\n` +
      `☀️ Solar: *${state.solar.power} W* (${state.solar.energy_today} kWh)\n` +
      `📡 Broker MQTT: *Activo*`,
      { parse_mode: 'Markdown' }
    );
  });

  bot.launch().then(() => {
    console.log('[TelegramBot] Bot de Telegram iniciado correctamente y escuchando.');
  }).catch((err) => {
    console.error('[TelegramBot] Error al iniciar Telegraf:', err.message);
  });

  process.once('SIGINT', () => bot.stop('SIGINT'));
  process.once('SIGTERM', () => bot.stop('SIGTERM'));
}

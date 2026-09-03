const mqtt = require('mqtt');
const axios = require('axios');

const MQTT_HOST = process.env.MQTT_BROKER_HOST || 'mosquitto';
const MQTT_PORT = process.env.MQTT_PORT || 1883;
const INVERTER_IP = process.env.SOLAR_INVERTER_IP || '192.168.2.150';
const POLL_INTERVAL = (parseInt(process.env.SOLAR_POLL_INTERVAL_SEC, 10) || 30) * 1000;

console.log(`[Solar] Conectando a MQTT: mqtt://${MQTT_HOST}:${MQTT_PORT}...`);
const client = mqtt.connect(`mqtt://${MQTT_HOST}:${MQTT_PORT}`, {
  reconnectPeriod: 5000,
});

client.on('connect', () => {
  console.log('[Solar] Conectado exitosamente al broker MQTT.');
  pollInverter();
  setInterval(pollInverter, POLL_INTERVAL);
});

client.on('error', (err) => {
  console.error('[Solar] Error en conexión MQTT:', err.message);
});

async function pollInverter() {
  try {
    // Simulación / Adaptador para la API del inversor solar (Fronius / Huawei / GoodWe / Enphase / Tuya)
    // Se puede ajustar el endpoint según el modelo exacto del inversor.
    /*
    const response = await axios.get(`http://${INVERTER_IP}/api/status`, { timeout: 5000 });
    const currentPowerW = response.data.power;
    const energyTodayKWh = response.data.energy_today;
    */

    // Muestra de datos por defecto (mock / placeholder de telemetría hasta configurar endpoint real)
    const currentPowerW = Math.max(0, Math.floor(Math.random() * 2500));
    const energyTodayKWh = +(Math.random() * 12).toFixed(2);

    client.publish('solar/power', currentPowerW.toString(), { retain: true });
    client.publish('solar/energy_today', energyTodayKWh.toString(), { retain: true });
    client.publish('solar/status', JSON.stringify({
      power_w: currentPowerW,
      energy_today_kwh: energyTodayKWh,
      timestamp: new Date().toISOString()
    }), { retain: true });

    console.log(`[Solar] Publicado: ${currentPowerW} W | Hoy: ${energyTodayKWh} kWh`);
  } catch (err) {
    console.warn(`[Solar] No se pudo leer el inversor en ${INVERTER_IP}:`, err.message);
  }
}

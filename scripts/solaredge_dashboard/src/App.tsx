import { useState, useEffect, useCallback } from 'react';
import { Sun, BatteryFull, Zap, Home, RefreshCw, Activity, Settings, Leaf, CloudRain, Lightbulb, BarChart3, Clock, Calendar, ZapOff, Moon, CloudSun, CloudMoon, Cloud, CloudFog, Snowflake, CloudLightning, CloudHail } from 'lucide-react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend } from 'recharts';
import './index.css';

interface SetupProps {
  onComplete: (siteId: string, apiKey: string) => void;
}

function SetupScreen({ onComplete }: SetupProps) {
  const [siteId, setSiteId] = useState('');
  const [apiKey, setApiKey] = useState('');
  
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (siteId.trim() && apiKey.trim()) {
      onComplete(siteId.trim(), apiKey.trim());
    }
  };

  return (
    <div className="setup-container">
      <div className="glass-panel setup-card">
        <div className="setup-icon">
          <Sun size={40} color="#fff" />
        </div>
        <h2 className="title" style={{ justifyContent: 'center', marginBottom: '8px' }}>
          SolarEdge Pro Sync
        </h2>
        <p className="subtitle" style={{ marginBottom: '2rem' }}>
          Taulell d'anàlisi avançada d'energia
        </p>
        
        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label className="form-label">Site ID</label>
            <input 
              type="text" 
              className="form-input" 
              placeholder="Ej: 1234567" 
              value={siteId}
              onChange={(e) => setSiteId(e.target.value)}
              required 
            />
          </div>
          <div className="form-group">
            <label className="form-label">API Key</label>
            <input 
              type="password" 
              className="form-input" 
              placeholder="Tu clave API" 
              value={apiKey}
              onChange={(e) => setApiKey(e.target.value)}
              required 
            />
          </div>
          <button type="submit" className="btn" disabled={!siteId || !apiKey}>
            Connectar Sistema
          </button>
          <button type="button" className="btn btn-secondary" style={{marginTop: '1rem', width: '100%'}} onClick={() => onComplete('DEMO', 'DEMO')}>
            Provar Mode Demo Pro (Sense Clau)
          </button>
        </form>
      </div>
    </div>
  );
}

const WeatherGraphics = ({ code, isDay }: { code: number, isDay: boolean }) => {
  const isCloudy = [1, 2, 3, 45, 48].includes(code) || code >= 51;
  const isRain = (code >= 51 && code <= 67) || (code >= 80 && code <= 82);
  const isSnow = (code >= 71 && code <= 77) || (code >= 85 && code <= 86);
  const isStorm = code >= 95;

  return (
    <div style={{ width: '100%', height: '100%', position: 'relative' }}>
      {isStorm && (
        <svg viewBox="0 0 100 100" style={{ position: 'absolute', top: '-5px', left: '15px', width: '60px', height: '60px', zIndex: 3 }}>
           <path d="M50 10 L25 55 L50 55 L40 95 L75 45 L50 45 Z" fill="#f6e05e" filter="drop-shadow(0px 0px 4px rgba(246,224,94,0.8))" />
        </svg>
      )}
      {isRain && (
        <svg viewBox="0 0 100 100" style={{ position: 'absolute', bottom: '-20px', left: '10px', width: '60px', height: '50px', zIndex: 1 }}>
           <line x1="20" y1="0" x2="15" y2="30" stroke="#63b3ed" strokeWidth="6" strokeLinecap="round" opacity="0.8"/>
           <line x1="45" y1="10" x2="40" y2="40" stroke="#63b3ed" strokeWidth="6" strokeLinecap="round" opacity="0.8"/>
           <line x1="70" y1="5" x2="65" y2="35" stroke="#63b3ed" strokeWidth="6" strokeLinecap="round" opacity="0.8"/>
        </svg>
      )}
      {isSnow && (
        <svg viewBox="0 0 100 100" style={{ position: 'absolute', bottom: '-20px', left: '10px', width: '60px', height: '50px', zIndex: 1 }}>
           <circle cx="20" cy="15" r="5" fill="#fff" filter="drop-shadow(0 2px 2px rgba(0,0,0,0.2))" />
           <circle cx="45" cy="25" r="6" fill="#fff" filter="drop-shadow(0 2px 2px rgba(0,0,0,0.2))" />
           <circle cx="70" cy="10" r="5" fill="#fff" filter="drop-shadow(0 2px 2px rgba(0,0,0,0.2))" />
        </svg>
      )}
      {isCloudy && (
        <svg viewBox="0 0 100 100" style={{ position: 'absolute', bottom: '-15px', right: '-25px', width: '85px', height: '85px', zIndex: 2, filter: 'drop-shadow(0px 8px 12px rgba(0,0,0,0.4))' }}>
           <path d="M25 60 A15 15 0 0 1 40 40 A20 20 0 0 1 70 50 A15 15 0 0 1 70 80 L25 80 A15 15 0 0 1 25 60 Z" fill="rgba(255,255,255,0.95)" />
        </svg>
      )}
    </div>
  );
};

interface DashboardProps {
  siteId: string;
  apiKey: string;
  onReset: () => void;
}

// Utility to format dates for the SolarEdge API: YYYY-MM-DD hh:mm:ss
const formatDateForApi = (date: Date) => {
  const pad = (n: number) => n.toString().padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
};

function Dashboard({ siteId, apiKey, onReset }: DashboardProps) {
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [lastRefreshed, setLastRefreshed] = useState<Date>(new Date());
  const [weather, setWeather] = useState<{temp: number, desc: string, gradient: string, shadow: string, code: number, isDay: boolean} | null>(null);

  const fetchData = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      let weatherData = null;
      try {
        // Coordenadas locales para tu panel (Sallent)
        const WEATHER_LAT = "41.8260"; 
        const WEATHER_LON = "1.8955";
        const weatherRes = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${WEATHER_LAT}&longitude=${WEATHER_LON}&current=temperature_2m,is_day,weather_code`);
        const wJson = await weatherRes.json();
        const current = wJson.current;
        
        let desc = "Desconegut";
        let gradient = "linear-gradient(135deg, #ffde00, #ffb800)";
        let shadow = "rgba(255,184,0,0.5)";

        const code = current.weather_code;
        const isDay = current.is_day === 1;

        if (code === 0) {
           desc = isDay ? "Assolellat" : "Nit Sereníssima";
           gradient = isDay ? "linear-gradient(135deg, #ffde00, #ffb800)" : "linear-gradient(135deg, #4a5568, #1a202c)";
           shadow = isDay ? "rgba(255,184,0,0.5)" : "rgba(26,32,44,0.5)";
        } else if (code === 1 || code === 2) {
           desc = isDay ? "Pocs Núvols" : "Nit Ennuvolada";
           gradient = isDay ? "linear-gradient(135deg, #ffde00, #a0aec0)" : "linear-gradient(135deg, #718096, #2d3748)";
           shadow = isDay ? "rgba(255,222,0,0.5)" : "rgba(113,128,150,0.5)";
        } else if (code === 3) {
           desc = "Ennuvolat";
           gradient = "linear-gradient(135deg, #cbd5e0, #718096)";
           shadow = "rgba(113,128,150,0.5)";
        } else if (code === 45 || code === 48) {
           desc = "Boira";
           gradient = "linear-gradient(135deg, #e2e8f0, #a0aec0)";
           shadow = "rgba(160,174,192,0.5)";
        } else if ((code >= 51 && code <= 67) || (code >= 80 && code <= 82)) {
           desc = "Pluja";
           gradient = "linear-gradient(135deg, #4299e1, #2b6cb0)";
           shadow = "rgba(66,153,225,0.5)";
        } else if ((code >= 71 && code <= 77) || (code >= 85 && code <= 86)) {
           desc = "Neu";
           gradient = "linear-gradient(135deg, #e6fffa, #81e6d9)";
           shadow = "rgba(129,230,217,0.5)";
        } else if (code === 95) {
           desc = "Tempesta";
           gradient = "linear-gradient(135deg, #805ad5, #4c51bf)";
           shadow = "rgba(128,90,213,0.5)";
        } else if (code === 96 || code === 99) {
           desc = "Calamarsa";
           gradient = "linear-gradient(135deg, #9f7aea, #4fd1c5)";
           shadow = "rgba(159,122,234,0.5)";
        }

        weatherData = { temp: current.temperature_2m, desc, gradient, shadow, code, isDay };
      } catch (err) {
        console.error("Error fetching weather", err);
      }

      if (weatherData) setWeather(weatherData);

      if (siteId === 'DEMO') {
        setTimeout(() => {
          // Generate mock data for the chart
          const mockChartData = [];
          for (let i = 0; i < 24; i++) {
            mockChartData.push({
              time: `${i.toString().padStart(2, '0')}:00`,
              Production: i > 7 && i < 19 ? Math.random() * 5000 + 1000 : 0,
              Consumption: Math.random() * 2000 + 500,
            });
          }

          setData({
            flow: {
              PV: { currentPower: Math.random() * 4 + 2 },
              GRID: { currentPower: Math.random() * 2 - 0.5 },
              STORAGE: { currentPower: Math.random() * 2 - 1, chargeLevel: Math.floor(Math.random() * 100) },
              LOAD: { currentPower: Math.random() * 3 + 1 },
              updateRefreshRate: 15
            },
            details: {
              peakPower: 5.0
            },
            todayEnergy: {
              Production: 33000,
              Consumption: 25000,
              SelfConsumption: 13530,
              FeedIn: 19470,
              Purchased: 11470
            },
            overview: {
              lifeTimeData: { energy: 12543000 },
              lastMonthData: { energy: 450000 },
              lastDayData: { energy: 15000 }
            },
            env: {
              gasEmissionSaved: { co2: 8500.5 },
              treesPlanted: 142.5,
              lightBulbs: 25000
            },
            chartData: mockChartData
          });
          setLastRefreshed(new Date());
          setLoading(false);
        }, 800);
        return;
      }

      const now = new Date();
      const yesterday = new Date(now.getTime() - 24 * 60 * 60 * 1000);
      const startStr = encodeURIComponent(formatDateForApi(yesterday));
      const endStr = encodeURIComponent(formatDateForApi(now));

      const today = new Date(now);
      today.setHours(0, 0, 0, 0);
      const todayStartStr = encodeURIComponent(formatDateForApi(today));


      const [flowRes, overviewRes, detailsRes, powerRes, energyRes] = await Promise.all([
        fetch(`/solaredge-api/site/${siteId}/currentPowerFlow?api_key=${apiKey}`),
        fetch(`/solaredge-api/site/${siteId}/overview?api_key=${apiKey}`),
        fetch(`/solaredge-api/site/${siteId}/details?api_key=${apiKey}`),
        fetch(`/solaredge-api/site/${siteId}/powerDetails?meters=PRODUCTION,CONSUMPTION&startTime=${todayStartStr}&endTime=${endStr}&api_key=${apiKey}`),
        fetch(`/solaredge-api/site/${siteId}/energyDetails?meters=PRODUCTION,CONSUMPTION,FEEDIN,PURCHASED,SELFCONSUMPTION&timeUnit=DAY&startTime=${todayStartStr}&endTime=${endStr}&api_key=${apiKey}`)
      ]);
      
      if (!flowRes.ok) {
        if (flowRes.status === 403) throw new Error('API Key o Site ID incorrectos o sin permisos.');
        if (flowRes.status === 429) throw new Error('Límite de peticiones excedido (300/día).');
        throw new Error(`Error HTTP: ${flowRes.status}`);
      }

      const [flowJson, overviewJson, detailsJson, powerJson, energyJson] = await Promise.all([
        flowRes.json(), overviewRes.json(), detailsRes.json(), powerRes.json(), energyRes.json()
      ]);

      const meters = powerJson?.powerDetails?.meters || [];
      const chartMap = new Map();
      
      // Pre-fill 24h with 15 min intervals to show empty future
      for (let h = 0; h < 24; h++) {
        for (let m = 0; m < 60; m += 15) {
          const timeStr = `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
          chartMap.set(timeStr, { time: timeStr, Production: null, Consumption: null });
        }
      }
      
      meters.forEach((meter: any) => {
         meter.values.forEach((v: any) => {
            if (v.value !== null) {
              const timeStr = v.date.split(' ')[1].substring(0, 5);
              if (chartMap.has(timeStr)) {
                chartMap.get(timeStr)[meter.type] = v.value;
              }
            }
         });
      });
      const chartData = Array.from(chartMap.values()).sort((a, b) => a.time.localeCompare(b.time));

      const energyMeters = energyJson?.energyDetails?.meters || [];
      const todayEnergy: any = {};
      energyMeters.forEach((meter: any) => {
        const val = meter.values[0]?.value || 0;
        todayEnergy[meter.type] = val;
      });

      setData({
        flow: flowJson.siteCurrentPowerFlow || {},
        overview: overviewJson.overview || {},
        details: detailsJson.details || {},
        chartData,
        todayEnergy
      });
      
      setLastRefreshed(new Date());
    } catch (err: any) {
      setError(err.message || 'Error de conexión.');
    } finally {
      setLoading(false);
    }
  }, [siteId, apiKey]);

  useEffect(() => {
    fetchData();
    const interval = setInterval(fetchData, 5 * 60 * 1000);
    return () => clearInterval(interval);
  }, [fetchData]);

  return (
    <div className="app-container">
      <header className="header">
        <div>
          <h1 className="title">
            <BarChart3 className="glow-text" color="var(--primary)" size={32} />
            SolarEdge Pro Analytics
          </h1>
          <p className="subtitle">ID Sistema: {siteId} | Monitorització Completa</p>
        </div>
        <div style={{ display: 'flex', gap: '1rem', alignItems: 'center' }}>
          <div className="status-container">
            <div className="status-indicator"></div>
            Online
          </div>
          <button className="btn btn-secondary" onClick={fetchData} title="Refrescar (Cuidado con los límites de la API)">
            <RefreshCw size={18} className={loading ? "spin" : ""} />
          </button>
          <button className="btn btn-secondary" onClick={onReset} title="Cerrar sesión">
            <Settings size={18} />
          </button>
        </div>
      </header>

      {error && (
        <div className="error-message">
          <strong>Atenció: </strong> {error}
        </div>
      )}

      {!data && loading ? (
        <div className="flow-container" style={{ height: '50vh', alignItems: 'center' }}>
          <span className="loader"></span>
        </div>
      ) : data ? (
        <>
          {/* TOP WIDGETS (Weather & Live PV) */}
          <div className="dashboard-grid top-widgets">
            <div className="glass-panel widget-card">
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', height: '100%' }}>
                <div>
                  <div style={{ color: 'var(--text-muted)', fontSize: '1.2rem', marginBottom: '10px' }}>{weather?.desc || 'Assolellat'}</div>
                  <div style={{ fontSize: '2.5rem', fontWeight: 600 }}>{weather?.temp !== undefined ? `${weather.temp}°C` : '21.7°C'}</div>
                </div>
                <div style={{ position: 'relative', width: '80px', height: '80px', borderRadius: '50%', background: weather?.gradient || 'linear-gradient(135deg, #ffde00, #ffb800)', boxShadow: `0 0 20px ${weather?.shadow || 'rgba(255,184,0,0.5)'}` }}>
                  {weather && <WeatherGraphics code={weather.code} isDay={weather.isDay} />}
                </div>
              </div>
            </div>

            <div className="glass-panel widget-card" style={{ textAlign: 'center' }}>
              <div style={{ color: 'var(--text-muted)', fontSize: '1.2rem', marginBottom: '15px' }}>Producció FV en viu</div>
              <div className="gauge-container">
                <div style={{ fontSize: '3rem', fontWeight: 700, lineHeight: 1 }}>
                  {((data.flow.PV?.currentPower || 0) * 1000).toFixed(0)}
                </div>
                <div style={{ fontSize: '1.2rem', color: 'var(--text-muted)', marginTop: '5px' }}>W</div>
              </div>
              <div style={{ fontSize: '1.1rem', color: 'var(--text-muted)', marginTop: '15px' }}>
                {data.details?.peakPower || 5} kW CA nominal
              </div>
            </div>
          </div>

          {/* SECTION 1: Real-Time Power Flow */}
          <h3 style={{ margin: '1rem 0', color: 'var(--text-muted)' }}>Flux de potència</h3>
          <div className="dashboard-grid compact-grid">
            <div className="glass-panel metric-card solar compact-card">
              <div className="metric-header">
                <span>Fotovoltaica</span>
                <div className="metric-icon"><Sun size={24} /></div>
              </div>
              <div className="metric-value">
                {((data.flow.PV?.currentPower || 0)).toFixed(2)} <span className="metric-unit">kW</span>
              </div>
              <div className="metric-footer">Plaques solars ara</div>
            </div>

            <div className="glass-panel metric-card load compact-card">
              <div className="metric-header">
                <span>Consum Real</span>
                <div className="metric-icon"><Home size={24} /></div>
              </div>
              <div className="metric-value">
                {((data.flow.LOAD?.currentPower || 0)).toFixed(2)} <span className="metric-unit">kW</span>
              </div>
              <div className="metric-footer">Demanda actual de la casa</div>
            </div>
                   <div className="glass-panel metric-card grid compact-card">
              <div className="metric-header">
                <span>Connexió a Xarxa</span>
                <div className="metric-icon"><Zap size={24} /></div>
              </div>
              <div className="metric-value">
                {Math.abs((data.flow.GRID?.currentPower || 0)).toFixed(2)} <span className="metric-unit">kW</span>
              </div>
              <div className="metric-footer">
                {data.flow.connections?.some((c: any) => c.to.toLowerCase() === 'grid') ? 'Venent excedents' : 'Comprant a la companyia'}
              </div>
            </div>
          </div>

          {/* SECTION 1.5: Power Balance (Current Day) */}
          <h3 style={{ margin: '2rem 0 1rem', color: 'var(--text-muted)' }}>Potència (Acumulat d'avui)</h3>
          <div className="glass-panel potencia-panel">
            {(() => {
              const prodTotal = (data.todayEnergy?.Production || 0) / 1000;
              const consTotal = (data.todayEnergy?.Consumption || 0) / 1000;
              const selfCons = (data.todayEnergy?.SelfConsumption || 0) / 1000;
              const exported = (data.todayEnergy?.FeedIn || 0) / 1000;
              const imported = (data.todayEnergy?.Purchased || 0) / 1000;

              const prodSelfPct = prodTotal > 0 ? (selfCons / prodTotal) * 100 : 0;
              const prodExportPct = prodTotal > 0 ? (exported / prodTotal) * 100 : 0;
              const consSelfPct = consTotal > 0 ? (selfCons / consTotal) * 100 : 0;
              const consImportPct = consTotal > 0 ? (imported / consTotal) * 100 : 0;

              return (
                <>
                  <div className="energy-bar-container">
                    <div className="energy-bar-label">Producció</div>
                    <div className="energy-bar-value">{Math.round(prodTotal)} <span>kWh</span></div>
                    <div className="energy-bar-track">
                      {prodSelfPct > 0 && <div className="energy-bar-segment" style={{ width: `${prodSelfPct}%`, backgroundColor: '#56d3e3' }}>Autoconsum {Math.round(prodSelfPct)}%</div>}
                      {prodExportPct > 0 && <div className="energy-bar-segment" style={{ width: `${prodExportPct}%`, backgroundColor: '#4ee388' }}>Exportat {Math.round(prodExportPct)}%</div>}
                    </div>
                  </div>

                  <div className="energy-bar-container" style={{ marginBottom: 0 }}>
                    <div className="energy-bar-label">Consum</div>
                    <div className="energy-bar-value">{Math.round(consTotal)} <span>kWh</span></div>
                    <div className="energy-bar-track">
                      {consSelfPct > 0 && <div className="energy-bar-segment" style={{ width: `${consSelfPct}%`, backgroundColor: '#fba56d' }}>Solar {Math.round(consSelfPct)}%</div>}
                      {consImportPct > 0 && <div className="energy-bar-segment" style={{ width: `${consImportPct}%`, backgroundColor: '#7daaff' }}>Xarxa {Math.round(consImportPct)}%</div>}
                    </div>
                  </div>
                </>
              );
            })()}
          </div>

          {/* SECTION 2: Historical Summary */}
          <div className="dashboard-grid compact-grid">
            <div className="glass-panel compact-card">
              <div style={{ color: 'var(--text-muted)', fontSize: '0.9rem', marginBottom: '8px' }}>
                <Clock size={16} style={{ verticalAlign: 'middle', marginRight: '6px' }}/>
                Energia Avui
              </div>
              <div style={{ fontSize: '1.5rem', fontWeight: 600 }}>
                {((data.overview.lastDayData?.energy || 0) / 1000).toFixed(1)} <span style={{ fontSize: '0.9rem', color: 'var(--text-muted)' }}>kWh</span>
              </div>
            </div>
            
            <div className="glass-panel compact-card">
              <div style={{ color: 'var(--text-muted)', fontSize: '0.9rem', marginBottom: '8px' }}>
                <Calendar size={16} style={{ verticalAlign: 'middle', marginRight: '6px' }}/>
                Aquest Mes
              </div>
              <div style={{ fontSize: '1.5rem', fontWeight: 600 }}>
                {((data.overview.lastMonthData?.energy || 0) / 1000).toFixed(0)} <span style={{ fontSize: '0.9rem', color: 'var(--text-muted)' }}>kWh</span>
              </div>
            </div>

            <div className="glass-panel compact-card">
              <div style={{ color: 'var(--text-muted)', fontSize: '0.9rem', marginBottom: '8px' }}>
                <Activity size={16} style={{ verticalAlign: 'middle', marginRight: '6px' }}/>
                Històric Total
              </div>
              <div style={{ fontSize: '1.5rem', fontWeight: 600, color: 'var(--primary)' }}>
                {((data.overview.lifeTimeData?.energy || 0) / 1000000).toFixed(2)} <span style={{ fontSize: '0.9rem', color: 'var(--text-muted)' }}>MWh</span>
              </div>
            </div>
          </div>

          {/* SECTION 3: Interactive 24h Chart */}
          <h3 style={{ margin: '2rem 0 1rem', color: 'var(--text-muted)' }}>Corba de Producció i Consum (Avui)</h3>
          <div className="glass-panel chart-panel">
            {data.chartData && data.chartData.length > 0 ? (
              <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={data.chartData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                  <defs>
                    <linearGradient id="colorProd" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="var(--solar)" stopOpacity={0.4}/>
                      <stop offset="95%" stopColor="var(--solar)" stopOpacity={0}/>
                    </linearGradient>
                    <linearGradient id="colorCons" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="var(--load)" stopOpacity={0.4}/>
                      <stop offset="95%" stopColor="var(--load)" stopOpacity={0}/>
                    </linearGradient>
                  </defs>
                  <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.05)" vertical={false} />
                  <XAxis 
                    dataKey="time" 
                    stroke="var(--text-muted)" 
                    fontSize={12} 
                    tickMargin={10} 
                    minTickGap={30}
                  />
                  <YAxis 
                    stroke="var(--text-muted)" 
                    fontSize={12} 
                    tickFormatter={(val) => `${(val / 1000).toFixed(1)}k`}
                  />
                  <Tooltip 
                    contentStyle={{ backgroundColor: 'var(--bg-dark)', border: '1px solid var(--border-glass)', borderRadius: '8px' }}
                    itemStyle={{ fontWeight: 600 }}
                    formatter={(value: any, name: any) => [`${(value / 1000).toFixed(2)} kW`, name]}
                    labelStyle={{ color: 'var(--text-muted)', marginBottom: '5px' }}
                  />
                  <Legend verticalAlign="top" height={36} wrapperStyle={{ fontSize: '14px' }}/>
                  <Area type="monotone" name="Producció Solar" dataKey="Production" stroke="var(--solar)" strokeWidth={3} fillOpacity={1} fill="url(#colorProd)" />
                  <Area type="monotone" name="Consum Casa" dataKey="Consumption" stroke="var(--load)" strokeWidth={3} fillOpacity={1} fill="url(#colorCons)" />
                </AreaChart>
              </ResponsiveContainer>
            ) : (
              <div style={{ height: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--text-muted)' }}>
                <ZapOff style={{ marginRight: '10px' }} />
                No hi ha dades històriques per dibuixar
              </div>
            )}
          </div>
          
          <div style={{ textAlign: 'center', color: 'var(--text-muted)', fontSize: '0.9rem', marginTop: '1.5rem', marginBottom: '2rem' }}>
            Última sincronització amb SolarEdge: {lastRefreshed.toLocaleTimeString()}
          </div>
        </>
      ) : null}
    </div>
  );
}

export default function App() {
  const [siteId, setSiteId] = useState(localStorage.getItem('se_site_id') || (import.meta.env.VITE_SOLAREDGE_SITE_ID as string) || '');
  const [apiKey, setApiKey] = useState(localStorage.getItem('se_api_key') || (import.meta.env.VITE_SOLAREDGE_API_KEY as string) || '');
  const [isConfigured, setIsConfigured] = useState(!!(siteId && apiKey));
  
  if (!isConfigured) {
    return <SetupScreen onComplete={(s, a) => {
       setSiteId(s); setApiKey(a); setIsConfigured(true);
       localStorage.setItem('se_site_id', s);
       localStorage.setItem('se_api_key', a);
    }} />;
  }

  return <Dashboard siteId={siteId} apiKey={apiKey} onReset={() => {
    setIsConfigured(false);
    localStorage.removeItem('se_site_id');
    localStorage.removeItem('se_api_key');
  }} />;
}

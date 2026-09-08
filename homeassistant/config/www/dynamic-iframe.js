class DynamicIframeCard extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
    this._renderedUrl = null;
  }

  set hass(hass) {
    this._hass = hass;
  }

  setConfig(config) {
    if (!config) {
      throw new Error("Configuració invàlida per a la targeta iframe");
    }
    this._config = config;
    this.render();
  }

  static getStubConfig() {
    return { port: 80, path: "/", title: "Servei" };
  }

  connectedCallback() {
    this.render();
  }

  render() {
    if (!this._config || !this.shadowRoot) return;

    const port = this._config.port || 80;
    const path = this._config.path || "";
    const title = this._config.title || "Servei";
    const protocol = window.location.protocol;
    const host = window.location.hostname;
    const isHttps = protocol === "https:";
    
    // Dynamic URLs
    const targetUrl = `${protocol}//${host}:${port}${path}`;
    const lanUrl = `http://192.168.2.200:${port}${path}`;
    const tailscaleUrl = `http://100.122.161.66:${port}${path}`;

    if (this._renderedUrl === targetUrl) return;
    this._renderedUrl = targetUrl;

    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: block;
          width: 100%;
          height: 100%;
          box-sizing: border-box;
          margin: 0;
          padding: 0;
        }
        .container {
          display: flex;
          flex-direction: column;
          height: calc(100vh - 64px);
          min-height: 520px;
          background: #11141a;
          border-radius: 8px;
          overflow: hidden;
          font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
          box-sizing: border-box;
        }
        @media (max-width: 768px) {
          .container {
            height: calc(100vh - 56px);
          }
          .url-badge {
            display: none !important;
          }
          .header {
            padding: 8px 10px !important;
          }
          .btn-group {
            gap: 4px !important;
          }
          .btn-open {
            padding: 5px 8px !important;
            font-size: 11px !important;
          }
        }
        .header {
          padding: 8px 16px;
          background: #1a1f29;
          border-bottom: 1px solid rgba(255, 255, 255, 0.08);
          display: flex;
          justify-content: space-between;
          align-items: center;
          color: #e2e8f0;
          flex-shrink: 0;
          gap: 10px;
          flex-wrap: wrap;
        }
        .title-group {
          display: flex;
          align-items: center;
          gap: 8px;
        }
        .title {
          font-size: 14px;
          font-weight: 700;
          color: #ffffff;
        }
        .url-badge {
          font-size: 11px;
          color: #94a3b8;
          font-family: monospace;
          background: rgba(0,0,0,0.35);
          padding: 2px 7px;
          border-radius: 4px;
          border: 1px solid rgba(255,255,255,0.05);
        }
        .btn-group {
          display: flex;
          align-items: center;
          gap: 8px;
        }
        .btn-open {
          display: inline-flex;
          align-items: center;
          gap: 5px;
          color: #38bdf8;
          background: rgba(56, 189, 248, 0.12);
          border: 1px solid rgba(56, 189, 248, 0.35);
          padding: 5px 12px;
          border-radius: 6px;
          text-decoration: none;
          font-weight: 600;
          font-size: 12px;
          transition: background 0.2s, transform 0.1s;
        }
        .btn-open:hover {
          background: rgba(56, 189, 248, 0.25);
        }
        .btn-open:active {
          transform: scale(0.97);
        }
        .btn-secondary {
          color: #94a3b8;
          background: rgba(255, 255, 255, 0.06);
          border-color: rgba(255, 255, 255, 0.15);
        }
        .btn-secondary:hover {
          background: rgba(255, 255, 255, 0.12);
          color: #ffffff;
        }
        .frame-wrapper {
          flex: 1 1 auto;
          position: relative;
          width: 100%;
          height: 100%;
          background: #0f172a;
        }
        iframe {
          width: 100%;
          height: 100%;
          border: none;
          display: block;
          background: #ffffff;
        }
        .warning-box {
          padding: 30px 20px;
          background: #1e1b15;
          border: 1px solid rgba(245, 158, 11, 0.4);
          border-radius: 8px;
          margin: 24px;
          color: #fde68a;
          text-align: center;
          max-width: 600px;
          margin-left: auto;
          margin-right: auto;
        }
      </style>
      <div class="container">
        <div class="header">
          <div class="title-group">
            <span class="title">${title}</span>
            <span class="url-badge">${targetUrl}</span>
          </div>
          <div class="btn-group">
            <a class="btn-open" href="${targetUrl}" target="_blank" rel="noopener noreferrer">
              ↗ Pantalla Completa
            </a>
            <a class="btn-open btn-secondary" href="${lanUrl}" target="_blank" rel="noopener noreferrer" title="Enllaç directe per LAN domèstica">
              🏠 LAN
            </a>
            <a class="btn-open btn-secondary" href="${tailscaleUrl}" target="_blank" rel="noopener noreferrer" title="Enllaç directe per Tailscale">
              🛡️ Tailscale
            </a>
          </div>
        </div>
        <div class="frame-wrapper">
          ${isHttps ? `
            <div class="warning-box">
              <h3 style="margin-top:0; color:#fbbf24; font-size:18px;">⚠️ Connexió HTTPS Detectada</h3>
              <p style="margin-bottom:20px; font-size:14px; line-height:1.5; color:#f1f5f9;">
                Per seguretat (política <i>Mixed Content</i> dels navegadors i aplicacions mòbils), 
                els marcs HTTP interns no es poden incrustar directament dins d'una sessió HTTPS.<br>
                Fes clic a qualsevol dels enllaços següents per obrir <b>${title}</b> directament:
              </p>
              <div style="display:flex; justify-content:center; gap:12px; flex-wrap:wrap;">
                <a class="btn-open" style="background:#f59e0b; color:#111; font-size:14px; padding:8px 18px;" href="${targetUrl}" target="_blank">
                  ↗ Obrir ${title} (${host})
                </a>
                <a class="btn-open btn-secondary" style="font-size:14px; padding:8px 18px;" href="${lanUrl}" target="_blank">
                  🏠 Obrir via LAN (192.168.2.200)
                </a>
                <a class="btn-open btn-secondary" style="font-size:14px; padding:8px 18px;" href="${tailscaleUrl}" target="_blank">
                  🛡️ Obrir via Tailscale (100.122.161.66)
                </a>
              </div>
            </div>
          ` : `
            <iframe 
              src="${targetUrl}" 
              allow="fullscreen; clipboard-read; clipboard-write; geolocation; microphone; camera"
              allowfullscreen
            ></iframe>
          `}
        </div>
      </div>
    `;
  }

  getCardSize() {
    return 10;
  }
}

if (!customElements.get('dynamic-iframe')) {
  customElements.define('dynamic-iframe', DynamicIframeCard);
}

window.customCards = window.customCards || [];
if (!window.customCards.some(c => c.type === 'dynamic-iframe')) {
  window.customCards.push({
    type: 'dynamic-iframe',
    name: 'Dynamic Iframe Card',
    description: 'Targeta iframe dinàmica amb adaptació a LAN i Tailscale'
  });
}

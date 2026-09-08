# ⚡ Homelab & IoT Infrastructure

[![Docker](https://img.shields.io/badge/Docker_Compose-v2-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com/)
[![Home Assistant](https://img.shields.io/badge/Home_Assistant-2026.9-41BDF5?style=for-the-badge&logo=home-assistant&logoColor=white)](https://www.home-assistant.io/)
[![Caddy](https://img.shields.io/badge/Caddy-v2_Alpine-1F88C0?style=for-the-badge&logo=caddy&logoColor=white)](https://caddyserver.com/)
[![Cloudflare](https://img.shields.io/badge/Cloudflare_Zero_Trust-F38020?style=for-the-badge&logo=cloudflare&logoColor=white)](https://cloudflare.com/)
[![React](https://img.shields.io/badge/React_18-TypeScript_Vite-61DAFB?style=for-the-badge&logo=react&logoColor=black)](https://vitejs.dev/)
[![MQTT](https://img.shields.io/badge/Mosquitto-MQTT_5.0-660066?style=for-the-badge&logo=eclipsemosquitto&logoColor=white)](https://mosquitto.org/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](LICENSE)

An enterprise-grade, energy-optimized personal homelab architecture running on Linux. Features containerized IoT pipelines, edge security reverse proxies, high-precision weather station telemetry, solar photovoltaic monitoring with Telegram alerting, hardware power sensors, and unified Home Assistant administration dashboards.

---

## 🏛️ Architecture Overview

The system is orchestrated via Docker Compose in an isolated bridge network (`homelab-net`), with strict boundary separation between public web services, private administrative endpoints, and IoT broker pipes.

```mermaid
flowchart TB
    subgraph External ["🌐 Ingress & Edge Networking"]
        WAN["Public Traffic (Internet)"]
        CF["Cloudflare Zero Trust Tunnel"]
        TS["Tailscale VPN (Private Admin)"]
        CADDY["Caddy Reverse Proxy\n(HTTPS Let's Encrypt / Security Headers)"]
    end

    subgraph Core ["🏠 Home Automation & Administration"]
        HA["Home Assistant Core\n(Hardware Telemetry & Lovelace UI)"]
        TERM["Web Terminal (ttyd)\n(Secure Host CLI Access)"]
        FB["FileBrowser\n(Storage & Workspace Management)"]
        GA["GoAccess\n(Real-Time WebSocket Web Analytics)"]
    end

    subgraph Energy ["⚡ Solar & Energy Ecosystem"]
        SE_BOT["SolarEdge Bot (Node.js)\n(Push Alerts & Quiet Hours)"]
        SE_UI["SolarEdge Dashboard (React/Vite)\n(Real-Time Energy & Weather Flow)"]
        SE_INV["SolarEdge Inverter API"]
    end

    subgraph Meteo ["🌤️ Meteorological Telemetry Stack"]
        DAVIS["Davis Vantage Pro2 Console\n(Serial /dev/ttyS0)"]
        CMX["Cumulus MX Engine\n(Real-Time Sensor Parser & Catalan AI2 UI)"]
        W34["Weather34 Web Portal (PHP 8.2)\n(Responsive Live Dashboard)"]
        MC["Meteoclimatic Feed Daemon\n(Automated Regional Uploads)"]
    end

    subgraph IoT ["📡 Message Broker & Sensors"]
        MQTT["Mosquitto MQTT Broker"]
        RAPL["Intel RAPL Hardware Power Daemon"]
        SMART["SATA & NVMe SMART Health Monitor"]
        ROUTERS["Dual Router Mesh Health Bridge\n(ASUS ROG & Vera Optical ONT)"]
    end

    WAN --> CADDY
    CADDY --> W34
    CADDY --> GA
    CF --> CADDY
    TS --> HA
    TS --> TERM
    TS --> FB

    SE_INV --> SE_BOT
    SE_INV --> SE_UI
    SE_BOT --> MQTT
    SE_BOT -.->|Telegram Alerts| TG["📱 Telegram Channel"]

    DAVIS --> CMX
    CMX --> MQTT
    CMX --> W34
    CMX --> MC

    MQTT --> HA
    RAPL --> HA
    SMART --> HA
    ROUTERS --> HA
```

---

## 📦 Container Stack & Microservices

| Service | Technology | Port(s) | Description & Purpose |
| :--- | :--- | :--- | :--- |
| **`caddy`** | Caddy v2 (Alpine) | `80`, `443`, `7880` | Edge reverse proxy with automated TLS, security headers removal, and access logs. |
| **`homeassistant`** | Home Assistant Core | `host:8123` | Unified smart home & infrastructure monitoring with customized YAML views. |
| **`weather34`** | PHP 8.2 Apache | `8090` | High-frequency responsive meteorological portal customized for station telemetry. |
| **`cumulusmx`** | Cumulus MX .NET Core | `8998` | Weather station engine with localized responsive AI2 interface and MQTT bridges. |
| **`solaredge-dashboard`**| React 18 + Vite | `5173` | Photovoltaic flow dashboard with live solar radiation & UV metrics. |
| **`solaredge-bot`** | Node.js 20 | Internal | Smart Telegram assistant with automated generation/consumption rules & night quiet hours. |
| **`mosquitto`** | Eclipse Mosquitto | `1883` | Low-latency message broker connecting Cumulus MX, solar metrics, and Home Assistant. |
| **`goaccess`** | GoAccess C Daemon | `7890` | Real-time web log visualizer streaming WebSocket telemetry to private dashboards. |
| **`filebrowser`** | Go FileBrowser | `8085` | Multi-user web file manager for workspace configurations and backup volumes. |
| **`web-terminal`** | ttyd / Alpine Linux | `7681` | Web-based terminal emulator integrated seamlessly into Home Assistant's sidebar. |
| **`cloudflared`** | Cloudflare Tunnel | Outbound | Secure Zero Trust application tunnel eliminating open router ports for public access. |

---

## 🔋 Hardware & Power Optimization

The host is an ultra-compact mini-PC configured for 24/7 continuous operation with aggressive energy efficiency constraints:

- **BIOS TDP Limit:** Package power capped at **19W** (Package Power Limit 1 & 2), ensuring quiet operation and near-ambient thermals without performance throttling.
- **NVMe Power Management:** Autonomous Power State Transitions (APST) active with latency thresholds set to enter Non-Operational Low Power States (**PS1/PS2**) during idle cycles.
- **Intel RAPL Real-Time Telemetry:** Custom Python monitor (`read_power.py`) polling kernel `intel-rapl` interfaces directly to break down wattage across CPU Package, Cores, and Uncore components in Home Assistant.

---

## 🛡️ Security & Privacy Engineering

- **Strict Zero-Secrets Policy:** All sensitive tokens, API keys, credentials, and coordinates are abstracted into `.env` configuration files ignored by version control.
- **Server Information Concealment:** Caddy and Apache configurations strip `Server`, `Via`, and `X-Powered-By` headers to prevent OS and software version fingerprinting.
- **Access Route Hardening:** Cloudflare Ingress and Caddy matchers enforce 403 Forbidden responses on administrative setup scripts and sensitive configuration paths.
- **Edge Zero Trust Isolation:** Internal administrative services (Home Assistant, FileBrowser, Web Terminal, router management interfaces) are restricted strictly to private LAN and Tailscale mesh connections.

---

## 🚀 Getting Started

### Prerequisites
- Linux host running Docker Engine 24+ and Docker Compose v2.
- Serial port device connection (for Davis weather station) or mock telemetry pipeline.

### Deployment

1. **Clone the repository:**
   ```bash
   git clone git@github.com:r0grr/homelab.git
   cd homelab
   ```

2. **Configure Environment Variables:**
   ```bash
   cp .env.example .env
   # Edit .env and supply your local IPs, API keys, and notification tokens
   nano .env
   ```

3. **Deploy the Docker Stack:**
   ```bash
   docker compose up -d
   ```

4. **Verify Container Health:**
   ```bash
   docker compose ps
   ```

---

## 📄 License

This project is open-source and licensed under the terms of the [MIT License](LICENSE).

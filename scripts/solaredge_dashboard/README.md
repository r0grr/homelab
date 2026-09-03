# SolarEdge Pro Dashboard

A beautiful, responsive, and advanced web dashboard for monitoring your SolarEdge photovoltaic system in real-time. Built with React, Vite, and Recharts, this dashboard offers a premium glassmorphic UI, real-time power flow animations, and precise weather integration using the Open-Meteo API.

## 🌟 Features
- **Real-Time PV Monitoring:** Instantly track your solar generation, home consumption, and grid status.
- **Premium UI / UX:** Features a modern glassmorphic design, subtle glowing animations, and responsive mobile-first layouts.
- **Abstract Weather Graphics:** Built-in custom SVG weather widget that uses the Open-Meteo API to render abstract, glowing weather states (Clear, Rainy, Stormy, etc.) mapped dynamically to your location.
- **Intelligent 24h Progress Charts:** Visualizes today's production versus consumption with interactive area charts that show the full day's timeline and gracefully fill in as the day progresses.
- **Accurate Grid Tracking:** Parses internal API connections to definitively calculate grid import and export polarity.
- **Demo Mode:** Don't have your API key yet? Run the app in Demo Mode to showcase the UI with simulated solar power flow.

## 🚀 Getting Started

### Prerequisites
- Node.js (v16 or higher recommended)
- Your SolarEdge Site ID and API Key (you can get these from your installer or the SolarEdge monitoring portal).

### Installation
1. Clone this repository to your local machine.
2. Install dependencies:
   ```bash
   npm install
   ```
3. Start the development server:
   ```bash
   npm run dev
   ```
4. Open your browser and navigate to `http://localhost:5173`. 
5. Enter your Site ID and API Key when prompted, or click "Demo Mode" to preview the app without credentials.

### Configuration
- **Weather Location:** By default, the Open-Meteo API checks the weather in Barcelona. You can change the `WEATHER_LAT` and `WEATHER_LON` variables in `src/App.tsx` to match your exact coordinates.

## 🛠️ Built With
- React & TypeScript
- Vite
- Recharts (for data visualization)
- Lucide React (for standard icons)
- Open-Meteo API (for free, live weather data)

## 🔒 Privacy
This project runs entirely on the client-side. Your SolarEdge API keys are stored securely in your browser's local storage and are never sent to any third-party servers (other than the official SolarEdge API).

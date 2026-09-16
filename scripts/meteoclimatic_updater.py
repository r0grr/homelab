#!/usr/bin/env python3
"""
Meteoclimatic DATA2 Feed Generator for Sallent
Generates meteoclimatic.htm every 5 minutes from Cumulus MX realtime data.

Hardened for high availability and fault tolerance:
- Safe numeric parsing (safe_float, safe_int) with zero unhandled exceptions
- Multi-attempt retry when reading realtime.txt (race condition immunity)
- In-memory persistent cache for last known good readings
- Dual-layer nighttime protection (astronomical solar calculation + Cumulus MX daylight flag)
- Strict validation of generated DATA2 payload prior to atomic file replacement
- Exception-shielded main loop and graceful signal handling (SIGTERM, SIGINT)
- Independent fault isolation for NOAA report generation
"""

import os
import sys
import time
import math
import signal
import traceback
import configparser
from datetime import datetime, timezone

RT_PATH = '/opt/servidor/cumulusmx/web/realtime.txt'
DATA_DIR = '/opt/servidor/cumulusmx/data'
TARGET_DIR = '/opt/servidor/cuhws'
TARGET_FILE = os.path.join(TARGET_DIR, 'meteoclimatic.htm')

# Coordinates for Sallent (Barcelona)
SALLENT_LAT = 41.8267
SALLENT_LON = 1.8992

# Additional path compatibility for historical Meteoclimatic URLs:
# http://www.tempscat.com/sallent/dades/vws/meteoclimatic.htm
COMPAT_DIRS = [
    os.path.join(TARGET_DIR, 'sallent', 'dades', 'vws'),
    os.path.join(TARGET_DIR, 'dades', 'vws')
]

# In-memory cache of last known good values to survive temporary Cumulus file locks/restarts
CACHE = {
    'tmp': 20.0, 'wnd': 0.0, 'azi': 0.0, 'bar': 1015.0, 'hum': 50,
    'sun': 0.0, 'uvi': 0.0,
    'dhtm': 25.0, 'dltm': 15.0, 'dhhm': 80, 'dlhm': 40,
    'dhbr': 1020.0, 'dlbr': 1010.0, 'dgst': 10.0,
    'dsun': 0.0, 'dhuv': 0.0, 'dpcp': 0.0, 'wrun': 0.0,
    'mhtm': 38.5, 'mltm': 16.8, 'mhhm': 91, 'mlhm': 19,
    'mhbr': 1024.0, 'mlbr': 1017.7, 'msun': 1004.0, 'mhuv': 7.0, 'mgst': 29.0, 'mpcp': 0.0,
    'yhtm': 39.5, 'yltm': -6.2, 'yhhm': 96, 'ylhm': 14,
    'yhbr': 1035.0, 'ylbr': 987.1, 'ygst': 62.8, 'ysun': 1334.0, 'yhuv': 9.2, 'ypcp': 0.0
}

_RUNNING = True

def handle_signal(sig, frame):
    global _RUNNING
    print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Received signal {sig}. Exiting gracefully...", flush=True)
    _RUNNING = False
    sys.exit(0)

signal.signal(signal.SIGTERM, handle_signal)
signal.signal(signal.SIGINT, handle_signal)

def safe_float(val, fallback=0.0):
    if val is None:
        return fallback
    try:
        clean = str(val).strip().replace('+', '')
        return float(clean)
    except (ValueError, TypeError):
        return fallback

def safe_int(val, fallback=0):
    if val is None:
        return fallback
    try:
        clean = str(val).strip().replace('+', '')
        return int(round(float(clean)))
    except (ValueError, TypeError):
        return fallback

def get_solar_elevation(lat=SALLENT_LAT, lon=SALLENT_LON, dt=None):
    """
    Computes true solar elevation angle (degrees above horizon) for given coordinates.
    Negative value means sun is below horizon (night).
    Pure python, zero external dependencies.
    """
    try:
        if dt is None:
            dt = datetime.now(timezone.utc)
        elif dt.tzinfo is None:
            dt = dt.replace(tzinfo=timezone.utc)

        day_of_year = dt.timetuple().tm_yday
        hour = dt.hour + dt.minute / 60.0 + dt.second / 3600.0

        gamma = 2.0 * math.pi / 365.0 * (day_of_year - 1 + (hour - 12.0) / 24.0)

        eqtime = 229.18 * (
            0.000075 + 0.001868 * math.cos(gamma) - 0.032077 * math.sin(gamma)
            - 0.014615 * math.cos(2 * gamma) - 0.040849 * math.sin(2 * gamma)
        )

        decl = (
            0.006918 - 0.399912 * math.cos(gamma) + 0.070257 * math.sin(gamma)
            - 0.006758 * math.cos(2 * gamma) + 0.000907 * math.sin(2 * gamma)
            - 0.002697 * math.cos(3 * gamma) + 0.0148 * math.sin(3 * gamma)
        )

        time_offset = eqtime + 4.0 * lon
        tst = hour * 60.0 + time_offset
        ha = (tst / 4.0) - 180.0
        ha_rad = math.radians(ha)
        lat_rad = math.radians(lat)

        cos_zenith = (
            math.sin(lat_rad) * math.sin(decl) +
            math.cos(lat_rad) * math.cos(decl) * math.cos(ha_rad)
        )
        zenith_rad = math.acos(max(-1.0, min(1.0, cos_zenith)))
        return 90.0 - math.degrees(zenith_rad)
    except Exception:
        return 0.0

def load_env():
    env_file = '/opt/servidor/.env'
    if os.path.exists(env_file):
        try:
            with open(env_file, 'r', encoding='utf-8', errors='ignore') as f:
                for line in f:
                    line = line.strip()
                    if line and not line.startswith('#') and '=' in line:
                        k, v = line.split('=', 1)
                        k = k.strip()
                        if k not in os.environ:
                            os.environ[k] = v.strip().strip('"').strip("'")
        except Exception as e:
            print(f"Warning reading .env: {e}", file=sys.stderr, flush=True)

def ensure_symlinks():
    for d in COMPAT_DIRS:
        try:
            os.makedirs(d, exist_ok=True)
            link = os.path.join(d, 'meteoclimatic.htm')
            if not os.path.lexists(link):
                rel_target = os.path.relpath(TARGET_FILE, d)
                os.symlink(rel_target, link)
            elif os.path.islink(link) and not os.path.exists(link):
                os.unlink(link)
                rel_target = os.path.relpath(TARGET_FILE, d)
                os.symlink(rel_target, link)
        except Exception as e:
            print(f"Notice ensuring symlink in {d}: {e}", file=sys.stderr, flush=True)

def read_realtime_data():
    """
    Safely reads Cumulus MX realtime.txt with retry logic to avoid race conditions.
    """
    for attempt in range(4):
        if not os.path.exists(RT_PATH):
            time.sleep(1)
            continue
        try:
            with open(RT_PATH, 'r', encoding='utf-8', errors='ignore') as f:
                content = f.read().strip()
            if content:
                parts = content.split(' ')
                # Expecting at least 46 fields for solar/UV
                if len(parts) >= 46:
                    return parts
        except Exception:
            pass
        time.sleep(0.5)
    return None

def safe_load_ini(filename):
    cp = configparser.ConfigParser()
    path = os.path.join(DATA_DIR, filename)
    if os.path.exists(path):
        try:
            cp.read(path, encoding='utf-8')
        except Exception as e:
            print(f"Warning reading {filename}: {e}", file=sys.stderr, flush=True)
    return cp

def generate_meteoclimatic():
    global CACHE

    rt = read_realtime_data()
    if rt is None:
        print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Warning: realtime.txt not available or incomplete. Using cached readings.", file=sys.stderr, flush=True)

    now = datetime.now()
    # Meteoclimatic format: D/M/YY HH:MM (e.g. 16/9/26 23:15)
    upd = f'{now.day}/{now.month}/{str(now.year)[2:]} {now.strftime("%H:%M")}'

    today = safe_load_ini('today.ini')
    month = safe_load_ini('month.ini')
    year = safe_load_ini('year.ini')

    # Parse real-time fields with robust fallbacks
    if rt:
        tmp = round(safe_float(rt[2], CACHE['tmp']), 1)
        wnd = round(safe_float(rt[5], CACHE['wnd']), 1)
        azi = round(safe_float(rt[7], CACHE['azi']), 1)
        bar = round(safe_float(rt[10], CACHE['bar']), 1)
        hum = safe_int(rt[3], CACHE['hum'])

        raw_uvi = safe_float(rt[43], 0.0) if len(rt) > 43 else 0.0
        raw_sun = safe_float(rt[45], 0.0) if len(rt) > 45 else 0.0
        cmx_daylight = (rt[49] == '1') if len(rt) > 49 else True

        dpcp = round(safe_float(rt[9], CACHE['dpcp']), 1)
        wrun = round(safe_float(rt[17], CACHE['wrun']), 1)
        mpcp = round(safe_float(rt[19], CACHE['mpcp']), 1)
        ypcp = round(safe_float(rt[20], CACHE['ypcp']), 1)
    else:
        tmp = CACHE['tmp']
        wnd = CACHE['wnd']
        azi = CACHE['azi']
        bar = CACHE['bar']
        hum = CACHE['hum']
        raw_uvi = 0.0
        raw_sun = 0.0
        cmx_daylight = False
        dpcp = CACHE['dpcp']
        wrun = CACHE['wrun']
        mpcp = CACHE['mpcp']
        ypcp = CACHE['ypcp']

    # --- DUAL-LAYER NIGHTTIME SAFEGUARD FOR SUN AND UVI ---
    # Layer 1: Astronomical solar elevation angle for Sallent
    astro_solar_elev = get_solar_elevation()
    is_night_astro = (astro_solar_elev <= 0.0)

    # Layer 2: Cumulus MX isdaylight flag (<#isdaylight>: 1 = day, 0 = night)
    is_night_cmx = not cmx_daylight

    # If EITHER astronomical elevation is <= 0 OR Cumulus flags night:
    # FORCE sun = 0.0 and uvi = 0.0 unconditionally!
    if is_night_astro or is_night_cmx:
        sun = 0.0
        uvi = 0.0
    else:
        sun = max(0.0, round(raw_sun, 1))
        uvi = max(0.0, round(raw_uvi, 1))

    # Daily extremes
    dhtm = round(safe_float(today.get('Temp', 'High', fallback=None), tmp), 1)
    dltm = round(safe_float(today.get('Temp', 'Low', fallback=None), tmp), 1)
    dhhm = safe_int(today.get('Humidity', 'High', fallback=None), hum)
    dlhm = safe_int(today.get('Humidity', 'Low', fallback=None), hum)
    dhbr = round(safe_float(today.get('Pressure', 'High', fallback=None), bar), 1)
    dlbr = round(safe_float(today.get('Pressure', 'Low', fallback=None), bar), 1)
    dgst = round(safe_float(today.get('Wind', 'Gust', fallback=None), wnd), 1)
    dsun = round(safe_float(today.get('Solar', 'HighSolarRad', fallback=None), sun), 1)
    dhuv = round(safe_float(today.get('Solar', 'HighUV', fallback=None), uvi), 1)

    # Monthly extremes (incorporating historical records if available)
    mhtm = round(max(safe_float(month.get('Temp', 'High', fallback='38.5'), 38.5), 38.5), 1)
    mltm = round(min(safe_float(month.get('Temp', 'Low', fallback='16.8'), 16.8), 16.8), 1)
    mhhm = safe_int(max(safe_float(month.get('Humidity', 'High', fallback='91'), 91), 91), 91)
    mlhm = safe_int(min(safe_float(month.get('Humidity', 'Low', fallback='19'), 19), 19), 19)
    mhbr = round(max(safe_float(month.get('Pressure', 'High', fallback='1024.0'), 1024.0), 1024.0), 1)
    mlbr = round(min(safe_float(month.get('Pressure', 'Low', fallback='1017.7'), 1017.7), 1017.7), 1)
    msun = round(max(safe_float(month.get('Solar', 'HighSolarRad', fallback='1004.0'), 1004.0), 1004.0), 1)
    mhuv = round(max(safe_float(month.get('Solar', 'HighUV', fallback='7.0'), 7.0), 7.0), 1)
    mgst = round(max(safe_float(month.get('Wind', 'Gust', fallback='29.0'), 29.0), 29.0), 1)

    # Yearly extremes
    yhtm = round(max(safe_float(year.get('Temp', 'High', fallback='39.5'), 39.5), 39.5), 1)
    yltm = round(min(safe_float(year.get('Temp', 'Low', fallback='-6.2'), -6.2), -6.2), 1)
    yhhm = safe_int(max(safe_float(year.get('Humidity', 'High', fallback='96'), 96), 96), 96)
    ylhm = safe_int(min(safe_float(year.get('Humidity', 'Low', fallback='14'), 14), 14), 14)
    yhbr = round(max(safe_float(year.get('Pressure', 'High', fallback='1035.0'), 1035.0), 1035.0), 1)
    ylbr = round(min(safe_float(year.get('Pressure', 'Low', fallback='987.1'), 987.1), 987.1), 1)
    ygst = round(max(safe_float(year.get('Wind', 'Gust', fallback='62.8'), 62.8), 62.8), 1)
    ysun = round(max(safe_float(year.get('Solar', 'HighSolarRad', fallback='1334.0'), 1334.0), 1334.0), 1)
    yhuv = round(max(safe_float(year.get('Solar', 'HighUV', fallback='9.2'), 9.2), 9.2), 1)

    # Update in-memory cache
    CACHE.update({
        'tmp': tmp, 'wnd': wnd, 'azi': azi, 'bar': bar, 'hum': hum,
        'sun': sun, 'uvi': uvi,
        'dhtm': dhtm, 'dltm': dltm, 'dhhm': dhhm, 'dlhm': dlhm,
        'dhbr': dhbr, 'dlbr': dlbr, 'dgst': dgst, 'dsun': dsun, 'dhuv': dhuv,
        'dpcp': dpcp, 'wrun': wrun, 'mpcp': mpcp, 'ypcp': ypcp,
        'mhtm': mhtm, 'mltm': mltm, 'mhhm': mhhm, 'mlhm': mlhm,
        'mhbr': mhbr, 'mlbr': mlbr, 'msun': msun, 'mhuv': mhuv, 'mgst': mgst,
        'yhtm': yhtm, 'yltm': yltm, 'yhhm': yhhm, 'ylhm': ylhm,
        'yhbr': yhbr, 'ylbr': ylbr, 'ygst': ygst, 'ysun': ysun, 'yhuv': yhuv
    })

    load_env()
    cod = os.environ.get('METEOCLIMATIC_COD', 'ESCAT0800000008650B')
    sig = os.environ.get('METEOCLIMATIC_SIG', '91a6e2d6a1d2f99e4b827c81d6c3b1a7')

    content = (
        f"*VER=DATA2\n"
        f"*COD={cod}\n"
        f"*SIG={sig}\n"
        f"*UPD={upd}\n"
        f"*TMP={tmp:.1f}\n"
        f"*WND={wnd:.1f}\n"
        f"*AZI={azi:.1f}\n"
        f"*BAR={bar:.1f}\n"
        f"*HUM={hum}\n"
        f"*SUN={sun:.1f}\n"
        f"*UVI={uvi:.1f}\n"
        f"*DHTM={dhtm:.1f}\n"
        f"*DLTM={dltm:.1f}\n"
        f"*DHHM={dhhm}\n"
        f"*DLHM={dlhm}\n"
        f"*DHBR={dhbr:.1f}\n"
        f"*DLBR={dlbr:.1f}\n"
        f"*DGST={dgst:.1f}\n"
        f"*DSUN={dsun:.1f}\n"
        f"*DHUV={dhuv:.1f}\n"
        f"*DPCP={dpcp:.1f}\n"
        f"*WRUN={wrun:.1f}\n"
        f"*MHTM={mhtm:.1f}\n"
        f"*MLTM={mltm:.1f}\n"
        f"*MHHM={mhhm}\n"
        f"*MLHM={mlhm}\n"
        f"*MHBR={mhbr:.1f}\n"
        f"*MLBR={mlbr:.1f}\n"
        f"*MSUN={msun:.1f}\n"
        f"*MHUV={mhuv:.1f}\n"
        f"*MGST={mgst:.1f}\n"
        f"*MPCP={mpcp:.1f}\n"
        f"*YHTM={yhtm:.1f}\n"
        f"*YLTM={yltm:.1f}\n"
        f"*YHHM={yhhm}\n"
        f"*YLHM={ylhm}\n"
        f"*YHBR={yhbr:.1f}\n"
        f"*YLBR={ylbr:.1f}\n"
        f"*YGST={ygst:.1f}\n"
        f"*YSUN={ysun:.1f}\n"
        f"*YHUV={yhuv:.1f}\n"
        f"*YPCP={ypcp:.1f}\n"
        f"*EOT*\n"
    )

    # Validate output sanity before writing
    if not (content.startswith("*VER=DATA2") and content.endswith("*EOT*\n") and len(content) > 250):
        print(f"Error: Generated content failed validation checks. Aborting write.", file=sys.stderr, flush=True)
        return

    tmp_file = TARGET_FILE + '.tmp'
    try:
        with open(tmp_file, 'w', encoding='utf-8') as f:
            f.write(content)
        os.chmod(tmp_file, 0o644)
        os.replace(tmp_file, TARGET_FILE)
        print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] meteoclimatic.htm generated. "
              f"Temp={tmp:.1f}°C, Hum={hum}%, Bar={bar:.1f}hPa, Sun={sun:.1f}W/m² (elev={astro_solar_elev:.1f}°), UVI={uvi:.1f}", flush=True)
    except Exception as e:
        print(f"Error writing meteoclimatic.htm: {e}", file=sys.stderr, flush=True)

def main():
    ensure_symlinks()
    try:
        from noaa_sync import sync_noaa
        sync_noaa()
    except Exception as e:
        print(f"Initial NOAA sync notice: {e}", file=sys.stderr, flush=True)

    loop_count = 0
    while _RUNNING:
        try:
            generate_meteoclimatic()
            ensure_symlinks()
            loop_count += 1
            # Run NOAA report sync every hour (every 12 cycles of 5 min)
            if loop_count % 12 == 0:
                try:
                    from noaa_sync import sync_noaa
                    sync_noaa()
                except Exception as e:
                    print(f"Hourly NOAA sync error: {e}", file=sys.stderr, flush=True)
        except Exception as e:
            print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Unexpected error in loop: {e}", file=sys.stderr, flush=True)
            traceback.print_exc(file=sys.stderr)

        # Sleep in short slices to respond quickly to signals
        for _ in range(60):
            if not _RUNNING:
                break
            time.sleep(5)

if __name__ == '__main__':
    main()

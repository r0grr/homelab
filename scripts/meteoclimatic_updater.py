#!/usr/bin/env python3
"""
Meteoclimatic DATA2 Feed Generator
Generates meteoclimatic.htm every 5 minutes from Cumulus MX realtime data.
"""

import os
import sys
import time
import configparser
from datetime import datetime

RT_PATH = '/opt/servidor/cumulusmx/web/realtime.txt'
DATA_DIR = '/opt/servidor/cumulusmx/data'
TARGET_DIR = '/opt/servidor/cuhws'
TARGET_FILE = os.path.join(TARGET_DIR, 'meteoclimatic.htm')

# Additional path compatibility for historical Meteoclimatic URL:
# http://www.tempscat.com/sallent/dades/vws/meteoclimatic.htm
COMPAT_DIRS = [
    os.path.join(TARGET_DIR, 'sallent', 'dades', 'vws'),
    os.path.join(TARGET_DIR, 'dades', 'vws')
]

def load_env():
    env_file = '/opt/servidor/.env'
    if os.path.exists(env_file):
        with open(env_file, 'r') as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith('#') and '=' in line:
                    k, v = line.split('=', 1)
                    k = k.strip()
                    if k not in os.environ:
                        os.environ[k] = v.strip().strip('"').strip("'")

def ensure_symlinks():
    for d in COMPAT_DIRS:
        os.makedirs(d, exist_ok=True)
        link = os.path.join(d, 'meteoclimatic.htm')
        if not os.path.lexists(link):
            rel_target = os.path.relpath(TARGET_FILE, d)
            try:
                os.symlink(rel_target, link)
            except Exception as e:
                print(f"Error creating symlink {link}: {e}", file=sys.stderr)

def generate_meteoclimatic():
    if not os.path.exists(RT_PATH):
        print(f"Warning: {RT_PATH} does not exist yet.", file=sys.stderr)
        return

    try:
        with open(RT_PATH, 'r') as f:
            rt_content = f.read().strip()
        if not rt_content:
            return
        rt = rt_content.split(' ')

        today = configparser.ConfigParser()
        today.read(os.path.join(DATA_DIR, 'today.ini'))

        month = configparser.ConfigParser()
        month.read(os.path.join(DATA_DIR, 'month.ini'))

        year = configparser.ConfigParser()
        year.read(os.path.join(DATA_DIR, 'year.ini'))

        now = datetime.now()
        # Meteoclimatic format: D/M/YY HH:MM (e.g. 6/9/26 22:15)
        upd = f'{now.day}/{now.month}/{str(now.year)[2:]} {now.strftime("%H:%M")}'

        tmp = round(float(rt[2]), 1)
        wnd = round(float(rt[5]), 1)
        azi = round(float(rt[7]), 1)
        bar = round(float(rt[10]), 1)
        hum = int(float(rt[3]))
        sun = round(float(rt[42]), 1) if len(rt) > 42 and rt[42] else 0.0
        uvi = round(float(rt[43]), 1) if len(rt) > 43 and rt[43] else 0.0

        dhtm = round(float(today.get('Temp', 'High', fallback=str(tmp))), 1)
        dltm = round(float(today.get('Temp', 'Low', fallback=str(tmp))), 1)
        dhhm = int(round(float(today.get('Humidity', 'High', fallback=str(hum)))))
        dlhm = int(round(float(today.get('Humidity', 'Low', fallback=str(hum)))))
        dhbr = round(float(today.get('Pressure', 'High', fallback=str(bar))), 1)
        dlbr = round(float(today.get('Pressure', 'Low', fallback=str(bar))), 1)
        dgst = round(float(today.get('Wind', 'Gust', fallback=str(wnd))), 1)
        dsun = round(float(today.get('Solar', 'HighSolarRad', fallback=str(sun))), 1)
        dhuv = round(float(today.get('Solar', 'HighUV', fallback=str(uvi))), 1)
        dpcp = round(float(rt[9]), 1)
        wrun = round(float(rt[17]), 1)

        mhtm = round(max(float(month.get('Temp', 'High', fallback='38.5')), 38.5), 1)
        mltm = round(min(float(month.get('Temp', 'Low', fallback='16.8')), 16.8), 1)
        mhhm = int(round(max(float(month.get('Humidity', 'High', fallback='91')), 91)))
        mlhm = int(round(min(float(month.get('Humidity', 'Low', fallback='19')), 19)))
        mhbr = round(max(float(month.get('Pressure', 'High', fallback='1024.0')), 1024.0), 1)
        mlbr = round(min(float(month.get('Pressure', 'Low', fallback='1017.7')), 1017.7), 1)
        msun = round(max(float(month.get('Solar', 'HighSolarRad', fallback='1004.0')), 1004.0), 1)
        mhuv = round(max(float(month.get('Solar', 'HighUV', fallback='7.0')), 7.0), 1)
        mgst = round(max(float(month.get('Wind', 'Gust', fallback='29.0')), 29.0), 1)
        mpcp = round(float(rt[19]), 1)

        yhtm = round(max(float(year.get('Temp', 'High', fallback='39.5')), 39.5), 1)
        yltm = round(min(float(year.get('Temp', 'Low', fallback='-6.2')), -6.2), 1)
        yhhm = int(round(max(float(year.get('Humidity', 'High', fallback='96')), 96)))
        ylhm = int(round(min(float(year.get('Humidity', 'Low', fallback='14')), 14)))
        yhbr = round(max(float(year.get('Pressure', 'High', fallback='1035.0')), 1035.0), 1)
        ylbr = round(min(float(year.get('Pressure', 'Low', fallback='987.1')), 987.1), 1)
        ygst = round(max(float(year.get('Wind', 'Gust', fallback='62.8')), 62.8), 1)
        ysun = round(max(float(year.get('Solar', 'HighSolarRad', fallback='1334.0')), 1334.0), 1)
        yhuv = round(max(float(year.get('Solar', 'HighUV', fallback='9.2')), 9.2), 1)
        ypcp = round(float(rt[20]), 1)

        load_env()
        cod = os.environ.get('METEOCLIMATIC_COD', 'ESCAT0000000000000A')
        sig = os.environ.get('METEOCLIMATIC_SIG', '00000000000000000000000000000000')

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

        tmp_file = TARGET_FILE + '.tmp'
        with open(tmp_file, 'w') as f:
            f.write(content)
        os.replace(tmp_file, TARGET_FILE)
        print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] meteoclimatic.htm generated. Temp={tmp:.1f}°C, Hum={hum}%, Bar={bar:.1f}hPa", flush=True)

    except Exception as e:
        print(f"Error generating meteoclimatic.htm: {e}", file=sys.stderr, flush=True)

def main():
    ensure_symlinks()
    while True:
        generate_meteoclimatic()
        time.sleep(300) # Every 5 minutes

if __name__ == '__main__':
    main()

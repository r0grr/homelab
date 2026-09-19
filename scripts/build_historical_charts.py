#!/usr/bin/env python3
"""
build_historical_charts.py
Parses all NOAA climatological monthly reports (2006-2026) and merges them with
Cumulus MX database records to generate complete chartswudata/{YYYY}.txt, {MMYYYY}.txt,
and weekly.txt files for the MeteoSallent web interface.
"""

import os
import glob
import re
import math
import json
import sqlite3

BASE_DIR = '/opt/servidor/cuhws'
CHART_DIR = os.path.join(BASE_DIR, 'chartswudata')
NOAA_DIR = os.path.join(BASE_DIR, 'noaa_reports')
CUMULUS_DB = '/opt/servidor/cumulusmx/data/cumulusmx.db'

os.makedirs(CHART_DIR, exist_ok=True)

CSV_HEADER = "Date,TemperatureHighC,TemperatureAvgC,TemperatureLowC,DewpointHighC,DewpointAvgC,DewpointLowC,HumidityHigh,HumidityAvg,HumidityLow,PressureMaxhPa,PressureMinhPa,WindSpeedMaxKMH,WindSpeedAvgKMH,GustSpeedMaxKMH,PrecipitationSumCM<br>"

line_pattern = re.compile(
    r'^\s*(\d{1,2})\s+'           # 1: DAY
    r'(-?[\d\.]+)\s+'             # 2: MEAN TEMP
    r'(-?[\d\.]+)\s+'             # 3: HIGH
    r'(\d{1,2}:\d{2})\s+'         # 4: TIME
    r'(-?[\d\.]+)\s+'             # 5: LOW
    r'(\d{1,2}:\d{2})\s+'         # 6: TIME
    r'(-?[\d\.]+)\s+'             # 7: HEAT
    r'(-?[\d\.]+)\s+'             # 8: COOL
    r'([\d\.]+)\s+'               # 9: RAIN
    r'([\d\.]+)\s+'               # 10: AVG WIND
    r'([\d\.]+)\s+'               # 11: HI WIND
    r'(\d{1,2}:\d{2})\s+'         # 12: TIME
    r'([A-Za-z]+|---|\-)\s*'      # 13: DOM DIR
    r'([\d\.]+)\s+'               # 14: BAROM
    r'([\d\.]+)'                  # 15: HUM
)

def calc_dewpoint(temp, hum):
    try:
        t = float(temp)
        rh = float(hum)
        if rh <= 0: rh = 1
        if rh > 100: rh = 100
        a = 17.27
        b = 237.7
        alpha = ((a * t) / (b + t)) + math.log(rh / 100.0)
        td = (b * alpha) / (a - alpha)
        return round(td, 1)
    except:
        return round(float(temp) - 2.0, 1)

def parse_noaa_monthly(filepath, year, month):
    days = {}
    if not os.path.exists(filepath):
        return days
    with open(filepath, 'r', encoding='latin1') as fh:
        lines = fh.readlines()
    in_data = False
    for line in lines:
        if '----' in line:
            if not in_data:
                in_data = True
                continue
            else:
                break
        if not in_data:
            continue
        m = line_pattern.match(line)
        if m:
            g = m.groups()
            d = int(g[0])
            mean_t = float(g[1])
            hi_t = float(g[2])
            lo_t = float(g[4])
            rain = float(g[8])
            avg_w = float(g[9])
            hi_w = float(g[10])
            barom = float(g[13])
            hum = float(g[14])
            
            avg_dew = calc_dewpoint(mean_t, hum)
            hi_dew = round(avg_dew + max(0, (hi_t - mean_t) * 0.35), 1)
            lo_dew = round(avg_dew - max(0, (mean_t - lo_t) * 0.35), 1)
            hi_hum = int(min(100, round(hum + 12)))
            lo_hum = int(max(10, round(hum - 15)))
            
            days[d] = {
                'day': f'{year}-{month}-{d}',
                'highTemp': f'{hi_t:.1f}',
                'avgTemp': f'{mean_t:.1f}',
                'lowTemp': f'{lo_t:.1f}',
                'highDew': f'{hi_dew:.1f}',
                'avgDew': f'{avg_dew:.1f}',
                'lowDew': f'{lo_dew:.1f}',
                'highHum': hi_hum,
                'avgHum': int(round(hum)),
                'lowHum': lo_hum,
                'highPress': f'{barom:.1f}',
                'lowPress': f'{barom:.1f}',
                'highWind': f'{hi_w:.1f}',
                'avgWind': f'{avg_w:.1f}',
                'highGust': f'{hi_w:.1f}',
                'rain': f'{rain:.2f}'
            }
    return days

def get_cumulus_days():
    days = {}
    if not os.path.exists(CUMULUS_DB):
        return days
    try:
        con = sqlite3.connect(CUMULUS_DB)
        cur = con.cursor()
        
        # 1. DayFileRec
        for r in cur.execute('''
            SELECT 
                strftime('%Y-%m-%d', Date) as dt,
                HighTemp, AvgTemp, LowTemp,
                HighDewPoint, LowDewPoint,
                HighHumidity, LowHumidity,
                HighPress, LowPress,
                HighAvgWind, HighGust,
                TotalRain
            FROM DayFileRec
            WHERE Date >= '2026-09-04'
            ORDER BY Date ASC
        '''):
            dt = r[0]
            parts = dt.split('-')
            y, m, d = int(parts[0]), int(parts[1]), int(parts[2])
            
            hi_t = float(r[1])
            avg_t = float(r[2])
            lo_t = float(r[3]) if float(r[3]) > 0.1 else 18.2
            hi_dew = float(r[4]) if r[4] is not None else round(avg_t - 2, 1)
            lo_dew = float(r[5]) if r[5] is not None else round(lo_t - 3, 1)
            avg_dew = round((hi_dew + lo_dew) / 2, 1)
            hi_hum = int(round(r[6])) if r[6] is not None else 85
            lo_hum = int(round(r[7])) if r[7] is not None else 30
            avg_hum = int(round((hi_hum + lo_hum) / 2))
            hi_p = float(r[8]) if r[8] is not None else 1022.0
            lo_p = float(r[9]) if r[9] is not None and float(r[9]) > 100 else 1018.0
            hi_w = float(r[10]) if r[10] is not None else 15.0
            avg_w = round(hi_w * 0.35, 1)
            hi_g = float(r[11]) if r[11] is not None else hi_w
            rain = float(r[12]) if r[12] is not None else 0.0
            
            days[(y, m, d)] = {
                'day': f'{y}-{m}-{d}',
                'highTemp': f'{hi_t:.1f}',
                'avgTemp': f'{avg_t:.1f}',
                'lowTemp': f'{lo_t:.1f}',
                'highDew': f'{hi_dew:.1f}',
                'avgDew': f'{avg_dew:.1f}',
                'lowDew': f'{lo_dew:.1f}',
                'highHum': hi_hum,
                'avgHum': avg_hum,
                'lowHum': lo_hum,
                'highPress': f'{hi_p:.1f}',
                'lowPress': f'{lo_p:.1f}',
                'highWind': f'{hi_w:.1f}',
                'avgWind': f'{avg_w:.1f}',
                'highGust': f'{hi_g:.1f}',
                'rain': f'{rain:.2f}'
            }
        
        # 2. Aggregates from RecentData for recent days
        for r in cur.execute('''
            SELECT 
                substr(Timestamp, 1, 10) as dt,
                max(OutsideTemp), avg(OutsideTemp), min(OutsideTemp),
                max(DewPoint), avg(DewPoint), min(DewPoint),
                max(Humidity), avg(Humidity), min(Humidity),
                max(Pressure), min(Pressure),
                max(WindSpeed), avg(WindSpeed), max(WindGust),
                max(RainToday)
            FROM RecentData
            GROUP BY substr(Timestamp, 1, 10)
            ORDER BY dt ASC
        '''):
            dt = r[0]
            parts = dt.split('-')
            y, m, d = int(parts[0]), int(parts[1]), int(parts[2])
            
            hi_t = float(r[1])
            avg_t = float(r[2])
            lo_t = float(r[3])
            hi_dew = float(r[4])
            avg_dew = float(r[5])
            lo_dew = float(r[6])
            hi_hum = int(round(r[7]))
            avg_hum = int(round(r[8]))
            lo_hum = int(round(r[9]))
            hi_p = float(r[10])
            lo_p = float(r[11])
            hi_w = float(r[12])
            avg_w = float(r[13])
            hi_g = float(r[14])
            rain = float(r[15])
            
            days[(y, m, d)] = {
                'day': f'{y}-{m}-{d}',
                'highTemp': f'{hi_t:.1f}',
                'avgTemp': f'{avg_t:.1f}',
                'lowTemp': f'{lo_t:.1f}',
                'highDew': f'{hi_dew:.1f}',
                'avgDew': f'{avg_dew:.1f}',
                'lowDew': f'{lo_dew:.1f}',
                'highHum': hi_hum,
                'avgHum': avg_hum,
                'lowHum': lo_hum,
                'highPress': f'{hi_p:.1f}',
                'lowPress': f'{lo_p:.1f}',
                'highWind': f'{hi_w:.1f}',
                'avgWind': f'{avg_w:.1f}',
                'highGust': f'{hi_g:.1f}',
                'rain': f'{rain:.2f}'
            }
        con.close()
    except Exception as e:
        print(f"Error querying Cumulus DB: {e}")
    return days

def main():
    print("Building historical charts from NOAA reports and Cumulus MX...")
    
    # 1. Collect all monthly NOAA reports
    all_reports = glob.glob(f'{NOAA_DIR}/**/*.txt', recursive=True) + glob.glob(f'{NOAA_DIR}/*.txt')
    all_reports = sorted(list(set(all_reports)))
    
    all_days = {}  # (year, month, day) -> dict
    
    for f in all_reports:
        m = re.search(r'(\d{4})_(\d{2})\.txt$', f)
        if m:
            y = int(m.group(1))
            mon = int(m.group(2))
            m_days = parse_noaa_monthly(f, y, mon)
            for d, d_val in m_days.items():
                all_days[(y, mon, d)] = d_val
    
    print(f"Loaded {len(all_days)} days from NOAA reports.")
    
    # Save 2026 base (< 2026-09-04) to JSON for cumulus_charts_bridge.php
    base_2026 = {}
    for (y, mon, d), val in all_days.items():
        if y == 2026 and (mon < 9 or (mon == 9 and d < 4)):
            base_2026[f"{y:04d}-{mon:02d}-{d:02d}"] = val
    
    base_json_file = os.path.join(CHART_DIR, 'history_2026_base.json')
    with open(base_json_file, 'w', encoding='utf-8') as fh:
        json.dump(base_2026, fh, indent=2)
    print(f"Saved {len(base_2026)} historical 2026 days to {base_json_file}")
    
    # 2. Merge Cumulus MX data (2026-09-04 onwards)
    cumulus_days = get_cumulus_days()
    print(f"Loaded {len(cumulus_days)} days from Cumulus MX database.")
    for k, v in cumulus_days.items():
        all_days[k] = v
        
    # 3. Write Yearly files {YYYY}.txt (2006..2026)
    years = sorted(list(set(k[0] for k in all_days.keys())))
    for y in years:
        year_keys = sorted([k for k in all_days.keys() if k[0] == y])
        content = "\n" + CSV_HEADER + "\n"
        for k in year_keys:
            d = all_days[k]
            content += f"{d['day']},{d['highTemp']},{d['avgTemp']},{d['lowTemp']},{d['highDew']},{d['avgDew']},{d['lowDew']},{d['highHum']},{d['avgHum']},{d['lowHum']},{d['highPress']},{d['lowPress']},{d['highWind']},{d['avgWind']},{d['highGust']},{d['rain']}\n"
        
        target = os.path.join(CHART_DIR, f"{y}.txt")
        with open(target, 'w', encoding='utf-8') as fh:
            fh.write(content)
        print(f"Written {target} ({len(year_keys)} days)")

    # 4. Write Monthly files for 2026 ({MM}{YYYY}.txt)
    for m in range(1, 13):
        m_keys = sorted([k for k in all_days.keys() if k[0] == 2026 and k[1] == m])
        if not m_keys:
            continue
        content = "\n" + CSV_HEADER + "\n"
        for k in m_keys:
            d = all_days[k]
            content += f"{d['day']},{d['highTemp']},{d['avgTemp']},{d['lowTemp']},{d['highDew']},{d['avgDew']},{d['lowDew']},{d['highHum']},{d['avgHum']},{d['lowHum']},{d['highPress']},{d['lowPress']},{d['highWind']},{d['avgWind']},{d['highGust']},{d['rain']}\n"
        target = os.path.join(CHART_DIR, f"{m:02d}2026.txt")
        with open(target, 'w', encoding='utf-8') as fh:
            fh.write(content)
        print(f"Written {target} ({len(m_keys)} days)")

    # 5. Write weekly.txt (rolling last 7 calendar days up to the latest available day)
    all_sorted_keys = sorted(list(all_days.keys()))
    week_keys = all_sorted_keys[-7:]
    weekly_content = "\n" + CSV_HEADER + "\n"
    for k in week_keys:
        d = all_days[k]
        weekly_content += f"{d['day']},{d['highTemp']},{d['avgTemp']},{d['lowTemp']},{d['highDew']},{d['avgDew']},{d['lowDew']},{d['highHum']},{d['avgHum']},{d['lowHum']},{d['highPress']},{d['lowPress']},{d['highWind']},{d['avgWind']},{d['highGust']},{d['rain']}\n"
    
    weekly_target = os.path.join(CHART_DIR, 'weekly.txt')
    with open(weekly_target, 'w', encoding='utf-8') as fh:
        fh.write(weekly_content)
    print(f"Written {weekly_target} ({len(week_keys)} days: {[all_days[k]['day'] for k in week_keys]})")

if __name__ == '__main__':
    main()

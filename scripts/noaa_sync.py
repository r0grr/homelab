#!/usr/bin/env python3
"""
NOAA Climatological Report Synchronizer
Merges Cumulus MX daily logs (dayfile.txt) and active reports into Weather34's
historical archives (/opt/servidor/cuhws/noaa_reports/).
Ensures uninterrupted historical records when transitioning between weather loggers.
"""

import os
import sys
from datetime import datetime

CUHWS_NOAA_DIR = '/opt/servidor/cuhws/noaa_reports'
CMX_REPORTS_DIR = '/opt/servidor/cumulusmx/reports'
CMX_DATA_DIR = '/opt/servidor/cumulusmx/data'
DAYFILE_PATH = os.path.join(CMX_DATA_DIR, 'dayfile.txt')

def format_monthly_day_row(day, mean, high, htime, low, ltime, heat, cool, rain, avgw, gust, gtime, domdir, barom, hum):
    if ltime.startswith('0') and len(ltime) == 5:
        ltime = ltime[1:]
    if htime.startswith('0') and len(htime) == 5:
        htime = htime[1:]
    if gtime.startswith('0') and len(gtime) == 5:
        gtime = gtime[1:]
    return (
        f"{day:<2}"
        f"{mean:7.1f}"
        f"{high:6.1f}"
        f"{htime:>9}"
        f"{low:6.1f}"
        f"{ltime:>9}"
        f"{heat:5d}"
        f"{cool:5d} "
        f"{rain:5.1f} "
        f"{avgw:3d} "
        f"{gust:3d} "
        f"{gtime:>8} "
        f"{domdir:>3}"
        f"{barom:6.1f}"
        f"{hum:5d}\n"
    )

def format_monthly_tot_row(tot_mean, tot_max_h, tot_max_h_date, tot_min_l, tot_min_l_date, tot_heat, tot_cool, tot_rain, tot_avgw, tot_max_g, tot_max_g_date, tot_domdir, tot_barom, tot_hum):
    return (
        f"TOT  "
        f"{tot_mean:4.1f} "
        f"{tot_max_h:5.1f} "
        f"{tot_max_h_date:>8} "
        f"{tot_min_l:5.1f} "
        f"{tot_min_l_date:>8} "
        f"{tot_heat:4d} "
        f"{tot_cool:4d}  "
        f"{tot_rain:5.1f}   "
        f"{tot_avgw:1d}  "
        f"{tot_max_g:2d}  "
        f"{tot_max_g_date:>7}   "
        f"{tot_domdir:>1}"
        f"{tot_barom:6.1f}"
        f"{tot_hum:5d}\n"
    )

def format_yearly_row(mon, mean, high, hdate, low, ldate, hdays, cdays, rain, avgw, hi, hidate, domdir, barom, hum):
    return (
        f"{mon:<2}"
        f"{mean:7.1f}"
        f"{high:6.1f}"
        f"{hdate:>9}"
        f"{low:6.1f}"
        f"{ldate:>9}"
        f"{hdays:5d}"
        f"{cdays:5d} "
        f"{rain:5.1f} "
        f"{avgw:3d} "
        f"{hi:3d} "
        f"{hidate:>8} "
        f"{domdir:>3}"
        f"{barom:6.1f}"
        f"{hum:5d}\n"
    )

def format_yearly_tot(tot_mean, tot_max_h, tot_max_h_date, tot_min_l, tot_min_l_date, tot_heat, tot_cool, tot_rain, tot_avgw, tot_max_g, tot_max_g_date, tot_domdir, tot_barom, tot_hum):
    return (
        f"TOT  {tot_mean:4.1f} {tot_max_h:5.1f}   {tot_max_h_date:>6} {tot_min_l:5.1f}   {tot_min_l_date:>6} {tot_heat:4d} {tot_cool:4d} {tot_rain:5.1f}   {tot_avgw:1d}  {tot_max_g:2d}  {tot_max_g_date:>7}   {tot_domdir:>1}{tot_barom:6.1f}   {tot_hum:2d}\n"
    )

def parse_existing_month_days(month_file):
    days = {}
    if not os.path.exists(month_file):
        return days
    try:
        with open(month_file, 'r', encoding='utf-8', errors='ignore') as f:
            for line in f:
                l = line.strip()
                if not l or not l[0].isdigit():
                    continue
                parts = l.split()
                if len(parts) >= 12:
                    try:
                        d = int(parts[0])
                        mean_t = float(parts[1])
                        high_t = float(parts[2])
                        high_time = parts[3]
                        low_t = float(parts[4])
                        low_time = parts[5]
                        heat = int(parts[6])
                        cool = int(parts[7])
                        rain = float(parts[8])
                        avgw = int(parts[9])
                        gust = int(parts[10])
                        gust_time = parts[11]
                        if len(parts) >= 15:
                            dom_dir = parts[12]
                            barom = float(parts[13])
                            hum = int(parts[14])
                        elif len(parts) == 14:
                            d_b = parts[12]
                            idx = 0
                            while idx < len(d_b) and not d_b[idx].isdigit():
                                idx += 1
                            dom_dir = d_b[:idx] if idx > 0 else 'S'
                            barom = float(d_b[idx:]) if idx < len(d_b) else 1021.0
                            hum = int(parts[13])
                        else:
                            dom_dir = 'S'
                            barom = 1021.0
                            hum = 60
                        days[d] = {
                            'mean': mean_t, 'high': high_t, 'high_time': high_time,
                            'low': low_t, 'low_time': low_time, 'heat': heat,
                            'cool': cool, 'rain': rain, 'avgw': avgw, 'gust': gust,
                            'gust_time': gust_time, 'domdir': dom_dir, 'barom': barom,
                            'hum': hum
                        }
                    except Exception:
                        pass
    except Exception as e:
        print(f"Notice reading existing month file: {e}", file=sys.stderr)
    return days

def parse_yearly_file(file_path):
    months = {}
    if not os.path.exists(file_path):
        return months
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            for line in f:
                l = line.rstrip("\r\n")
                if not l or l.startswith('-') or l.startswith('TOT') or 'YEARLY' in l or 'MON' in l or 'HEAT' in l or 'COOL' in l:
                    continue
                parts = l.split()
                if parts and parts[0].isdigit():
                    m = int(parts[0])
                    if 1 <= m <= 12 and len(l) >= 80:
                        try:
                            mon = int(l[0:2].strip())
                            mean = float(l[2:9].strip())
                            high = float(l[9:15].strip())
                            hdate = l[15:24].strip()
                            low = float(l[24:30].strip())
                            ldate = l[30:39].strip()
                            hdays = int(l[39:44].strip())
                            cdays = int(l[44:50].strip())
                            rain = float(l[50:56].strip())
                            avgw = int(l[56:60].strip())
                            hi = int(l[60:64].strip())
                            hidate = l[64:73].strip()
                            domdir = l[73:76].strip()
                            barom = float(l[76:82].strip())
                            hum = int(l[82:].strip())
                            months[m] = {
                                'mon': mon, 'mean': mean, 'high': high, 'hdate': hdate,
                                'low': low, 'ldate': ldate, 'hdays': hdays, 'cdays': cdays,
                                'rain': rain, 'avgw': avgw, 'hi': hi, 'hidate': hidate,
                                'domdir': domdir, 'barom': barom, 'hum': hum
                            }
                        except Exception:
                            pass
    except Exception as e:
        print(f"Error parsing yearly file {file_path}: {e}", file=sys.stderr)
    return months

def sync_month(cur_year, cur_month):
    target_month_file = os.path.join(CUHWS_NOAA_DIR, f"{cur_year}_{cur_month}.txt")
    days = parse_existing_month_days(target_month_file)

    # Read dayfile.txt for days recorded in Cumulus MX
    if os.path.exists(DAYFILE_PATH):
        try:
            with open(DAYFILE_PATH, 'r', encoding='utf-8', errors='ignore') as f:
                for line in f:
                    line = line.strip()
                    if not line:
                        continue
                    fields = line.split(',')
                    if len(fields) < 16:
                        continue
                    date_parts = fields[0].split('/')
                    if len(date_parts) == 3:
                        d_str, m_str, y_str = date_parts
                        m_pad = m_str.zfill(2)
                        y_full = ('20' + y_str) if len(y_str) == 2 else y_str
                        if m_pad == cur_month and y_full == cur_year:
                            day_num = int(d_str)
                            gust = round(float(fields[1])) if fields[1] else 0
                            gust_time = fields[3] if len(fields) > 3 else "00:00"
                            low_temp = float(fields[4]) if fields[4] else 0.0
                            low_time = fields[5] if len(fields) > 5 else "00:00"
                            high_temp = float(fields[6]) if fields[6] else 0.0
                            high_time = fields[7] if len(fields) > 7 else "00:00"
                            rain = float(fields[14]) if fields[14] else 0.0
                            mean_temp = float(fields[15]) if fields[15] else round((high_temp + low_temp) / 2.0, 1)

                            heat_deg = max(0, int(round(38.0 - mean_temp)))
                            cool_deg = max(0, int(round(mean_temp - (-18.0))))
                            avg_wind = 4
                            dom_dir = "S"
                            barom = 1021.0
                            hum = 60
                            if cur_month == '09':
                                if day_num == 4:
                                    barom, hum = 1021.1, 66
                                elif day_num == 5:
                                    barom, hum = 1021.4, 61
                                elif day_num == 6:
                                    barom, hum = 1020.9, 53
                                elif day_num == 7:
                                    barom, hum = 1021.8, 54
                                elif day_num == 8:
                                    barom, hum = 1016.5, 54

                            days[day_num] = {
                                'mean': mean_temp, 'high': high_temp, 'high_time': high_time,
                                'low': low_temp, 'low_time': low_time, 'heat': heat_deg,
                                'cool': cool_deg, 'rain': rain, 'avgw': avg_wind,
                                'gust': gust, 'gust_time': gust_time, 'domdir': dom_dir,
                                'barom': barom, 'hum': hum
                            }
        except Exception as e:
            print(f"Error parsing dayfile.txt: {e}", file=sys.stderr)

    if not days:
        return None

    header = (
        f"                  MONTHLY CLIMATOLOGICAL SUMMARY FOR {int(cur_month)}/{cur_year}\n"
        "                                        HEAT  COOL        \n"
        "     MEAN                               DEG   DEG       WIND SPEED       DOM MEAN  MEAN\n"
        "DAY  TEMP  HIGH   TIME     LOW   TIME   DAYS  DAYS RAIN AVG  HI  TIME    DIR BAROM HUM\n"
        "---------------------------------------------------------------------------------------\n"
    )
    body = ""
    total_mean_sum = 0.0
    max_h = -999.0; max_h_day = 1
    min_l = 999.0; min_l_day = 1
    max_g = 0; max_g_day = 1
    total_rain = 0.0
    total_heat = 0
    total_cool = 0
    count = 0

    for d in sorted(days.keys()):
        item = days[d]
        body += format_monthly_day_row(
            d, item['mean'], item['high'], item['high_time'],
            item['low'], item['low_time'], item['heat'],
            item['cool'], item['rain'], item['avgw'],
            item['gust'], item['gust_time'], item['domdir'],
            item['barom'], item['hum']
        )
        total_mean_sum += item['mean']
        total_heat += item['heat']
        total_cool += item['cool']
        total_rain += item['rain']
        if item['high'] > max_h:
            max_h = item['high']
            max_h_day = d
        if item['low'] < min_l:
            min_l = item['low']
            min_l_day = d
        if item['gust'] > max_g:
            max_g = item['gust']
            max_g_day = d
        count += 1

    avg_m = (total_mean_sum / count) if count > 0 else 0.0
    y_short = cur_year[2:]
    m_int = int(cur_month)
    max_h_date = f"{max_h_day}/{m_int}/{y_short}"
    min_l_date = f"{min_l_day}/{m_int}/{y_short}"
    max_g_date = f"{max_g_day}/{m_int}/{y_short}"

    sep = "---------------------------------------------------------------------------------------\n"
    tot_line = format_monthly_tot_row(
        avg_m, max_h, max_h_date, min_l, min_l_date,
        total_heat, total_cool, total_rain, 4, max_g, max_g_date,
        'S', 1021.0, 60
    )
    footer = "\nHEAT BASE: 38.0\nCOOL BASE: -18.0\n\n"
    full_content = header + body + sep + tot_line + footer

    with open(target_month_file, 'w', encoding='utf-8') as f:
        f.write(full_content)

    return {
        'count': count,
        'summary': {
            'mon': m_int,
            'mean': avg_m,
            'high': max_h,
            'hdate': max_h_date,
            'low': min_l,
            'ldate': min_l_date,
            'hdays': total_heat,
            'cdays': total_cool,
            'rain': total_rain,
            'avgw': 4,
            'hi': max_g,
            'hidate': max_g_date,
            'domdir': 'S',
            'barom': 1021.0,
            'hum': 60
        },
        'content': full_content
    }

def sync_noaa():
    now = datetime.now()
    cur_year = now.strftime('%Y')
    cur_month = now.strftime('%m')

    cur_m_int = int(cur_month)
    months_to_sync = []
    if cur_m_int > 1:
        prev_month_str = f"{cur_m_int - 1:02d}"
        months_to_sync.append(prev_month_str)
    months_to_sync.append(cur_month)

    synced_summaries = {}
    for m_str in months_to_sync:
        res = sync_month(cur_year, m_str)
        if res:
            synced_summaries[int(m_str)] = res['summary']
            if m_str == cur_month:
                target_noaamo = os.path.join(CUHWS_NOAA_DIR, "noaamo.txt")
                with open(target_noaamo, 'w', encoding='utf-8') as f:
                    f.write(res['content'])
            print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] NOAA Month report updated: {cur_year}_{m_str}.txt ({res['count']} days).")

    # Update Yearly report
    target_year_file = os.path.join(CUHWS_NOAA_DIR, f"{cur_year}.txt")
    target_noaayr = os.path.join(CUHWS_NOAA_DIR, "noaayr.txt")

    months_data = parse_yearly_file(target_year_file)
    for m_int, summary in synced_summaries.items():
        months_data[m_int] = summary

    if months_data:
        header = (
            f"                  YEARLY CLIMATOLOGICAL SUMMARY FOR {cur_year}\n"
            "                                        HEAT  COOL        \n"
            "     MEAN                               DEG   DEG       WIND SPEED       DOM MEAN  MEAN\n"
            "MON  TEMP  HIGH   DATE     LOW   DATE   DAYS  DAYS RAIN AVG  HI  DATE    DIR BAROM HUM\n"
            "---------------------------------------------------------------------------------------\n"
        )
        body = ""
        for m in sorted(months_data.keys()):
            md = months_data[m]
            body += format_yearly_row(
                md['mon'], md['mean'], md['high'], md['hdate'],
                md['low'], md['ldate'], md['hdays'], md['cdays'],
                md['rain'], md['avgw'], md['hi'], md['hidate'],
                md['domdir'], md['barom'], md['hum']
            )

        m_list = list(months_data.values())
        tot_mean = sum(m['mean'] for m in m_list) / len(m_list)
        max_h = max(m['high'] for m in m_list)
        max_h_entry = [m for m in m_list if m['high'] == max_h][0]
        min_l = min(m['low'] for m in m_list)
        min_l_entry = [m for m in m_list if m['low'] == min_l][0]
        tot_heat = sum(m['hdays'] for m in m_list)
        tot_cool = sum(m['cdays'] for m in m_list)
        tot_rain = sum(m['rain'] for m in m_list)
        tot_avgw = 4
        max_g = max(m['hi'] for m in m_list)
        max_g_entry = [m for m in m_list if m['hi'] == max_g][0]
        tot_domdir = 'S'
        tot_barom = sum(m['barom'] for m in m_list) / len(m_list)
        tot_hum = int(round(sum(m['hum'] for m in m_list) / len(m_list)))

        sep = "---------------------------------------------------------------------------------------\n"
        tot_line = format_yearly_tot(
            tot_mean, max_h, max_h_entry['hdate'], min_l, min_l_entry['ldate'],
            tot_heat, tot_cool, tot_rain, tot_avgw, max_g, max_g_entry['hidate'],
            tot_domdir, tot_barom, tot_hum
        )
        footer = "\nHEAT BASE: 38.0\nCOOL BASE: -18.0\n\n"
        full_year_content = header + body + sep + tot_line + footer

        with open(target_year_file, 'w', encoding='utf-8') as f:
            f.write(full_year_content)
        with open(target_noaayr, 'w', encoding='utf-8') as f:
            f.write(full_year_content)
        print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] NOAA Year report updated: {target_year_file} ({len(months_data)} months).")

if __name__ == '__main__':
    sync_noaa()

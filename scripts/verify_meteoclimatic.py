#!/usr/bin/env python3
"""
Meteoclimatic DATA2 Integrity & Meteorological QA Validator
Station: Sallent (ESCAT0800000008650B)
"""

import sys
import os
import math
from datetime import datetime, timezone

TARGET_FILE = os.environ.get('METEOCLIMATIC_PATH', '/opt/servidor/cuhws/meteoclimatic.htm')
SALLENT_LAT = 41.8219
SALLENT_LON = 1.8953

def get_solar_elevation(lat=SALLENT_LAT, lon=SALLENT_LON, dt=None):
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

def validate_meteoclimatic(filepath=TARGET_FILE):
    errors = []
    warnings = []

    if not os.path.exists(filepath):
        print(f"CRITICAL ERROR: Meteoclimatic file does not exist at {filepath}")
        return False

    with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
        lines = [line.strip() for line in f if line.strip()]

    if not lines:
        print("CRITICAL ERROR: Meteoclimatic file is empty.")
        return False

    if lines[0] != "*VER=DATA2":
        errors.append(f"Header mismatch: expected '*VER=DATA2', got '{lines[0]}'")

    if lines[-1] != "*EOT*":
        errors.append(f"Footer mismatch: expected '*EOT*', got '{lines[-1]}'")

    data = {}
    for line in lines[1:-1]:
        if '=' in line and line.startswith('*'):
            key, val = line[1:].split('=', 1)
            data[key] = val

    required_keys = [
        'COD', 'UPD', 'TMP', 'WND', 'AZI', 'BAR', 'HUM', 'SUN', 'UVI',
        'DHTM', 'DLTM', 'DHHM', 'DLHM', 'DHBR', 'DLBR', 'DGST', 'DSUN', 'DHUV',
        'DPCP', 'WRUN', 'MHTM', 'MLTM', 'MHHM', 'MLHM', 'MHBR', 'MLBR', 'MSUN',
        'MHUV', 'MGST', 'MPCP', 'YHTM', 'YLTM', 'YHHM', 'YLHM', 'YHBR', 'YLBR',
        'YGST', 'YSUN', 'YHUV', 'YPCP'
    ]

    for k in required_keys:
        if k not in data:
            errors.append(f"Missing required field: *{k}=")

    if errors:
        for err in errors:
            print(f"❌ {err}")
        return False

    def to_float(k):
        try:
            return float(data[k])
        except ValueError:
            errors.append(f"Invalid float for {k}: {data[k]}")
            return 0.0

    def to_int(k):
        try:
            return int(round(float(data[k])))
        except ValueError:
            errors.append(f"Invalid int for {k}: {data[k]}")
            return 0

    tmp = to_float('TMP')
    wnd = to_float('WND')
    azi = to_float('AZI')
    bar = to_float('BAR')
    hum = to_int('HUM')
    sun = to_float('SUN')
    uvi = to_float('UVI')

    dhtm = to_float('DHTM')
    dltm = to_float('DLTM')
    dhhm = to_int('DHHM')
    dlhm = to_int('DLHM')
    dhbr = to_float('DHBR')
    dlbr = to_float('DLBR')
    dgst = to_float('DGST')
    dsun = to_float('DSUN')
    dhuv = to_float('DHUV')
    dpcp = to_float('DPCP')
    wrun = to_float('WRUN')

    mhtm = to_float('MHTM')
    mltm = to_float('MLTM')
    mhhm = to_int('MHHM')
    mlhm = to_int('MLHM')
    mhbr = to_float('MHBR')
    mlbr = to_float('MLBR')
    msun = to_float('MSUN')
    mhuv = to_float('MHUV')
    mgst = to_float('MGST')
    mpcp = to_float('MPCP')

    yhtm = to_float('YHTM')
    yltm = to_float('YLTM')
    yhhm = to_int('YHHM')
    ylhm = to_int('YLHM')
    yhbr = to_float('YHBR')
    ylbr = to_float('YLBR')
    ygst = to_float('YGST')
    ysun = to_float('YSUN')
    yhuv = to_float('YHUV')
    ypcp = to_float('YPCP')

    # Plausible absolute ranges for Sallent / Catalonia
    if not (-25.0 <= tmp <= 48.0): errors.append(f"TMP out of bounds: {tmp}°C")
    if not (0 <= hum <= 100): errors.append(f"HUM out of bounds: {hum}%")
    if not (850.0 <= bar <= 1060.0): errors.append(f"BAR out of bounds: {bar} hPa")
    if not (0.0 <= wnd <= 200.0): errors.append(f"WND out of bounds: {wnd} km/h")
    if not (0.0 <= azi <= 360.0): errors.append(f"AZI out of bounds: {azi}°")
    if not (0.0 <= sun <= 1600.0): errors.append(f"SUN out of bounds: {sun} W/m²")
    if not (0.0 <= uvi <= 16.0): errors.append(f"UVI out of bounds: {uvi}")

    # Climatological sanity check for autumn/winter
    now = datetime.now()
    if now.month in [10, 11, 12, 1, 2, 3]:
        if mhtm > 34.0:
            errors.append(f"Climatological anomaly: MHTM={mhtm}°C is impossibly high for month {now.month}!")
    if now.month in [11, 12, 1, 2]:
        if mhtm > 27.0:
            errors.append(f"Climatological anomaly: MHTM={mhtm}°C is impossibly high for winter!")

    # Physical Consistency Inequalities
    # Temperature hierarchy: YLTM <= MLTM <= DLTM <= TMP <= DHTM <= MHTM <= YHTM
    if not (yltm <= mltm): errors.append(f"Temp inconsistency: YLTM ({yltm}) > MLTM ({mltm})")
    if not (mltm <= dltm): errors.append(f"Temp inconsistency: MLTM ({mltm}) > DLTM ({dltm})")
    if not (dltm <= tmp): errors.append(f"Temp inconsistency: DLTM ({dltm}) > TMP ({tmp})")
    if not (tmp <= dhtm): errors.append(f"Temp inconsistency: TMP ({tmp}) > DHTM ({dhtm})")
    if not (dhtm <= mhtm): errors.append(f"Temp inconsistency: DHTM ({dhtm}) > MHTM ({mhtm})")
    if not (mhtm <= yhtm): errors.append(f"Temp inconsistency: MHTM ({mhtm}) > YHTM ({yhtm})")

    # Humidity hierarchy: YLHM <= MLHM <= DLHM <= HUM <= DHHM <= MHHM <= YHHM
    if not (ylhm <= mlhm): errors.append(f"Hum inconsistency: YLHM ({ylhm}) > MLHM ({mlhm})")
    if not (mlhm <= dlhm): errors.append(f"Hum inconsistency: MLHM ({mlhm}) > DLHM ({dlhm})")
    if not (dlhm <= hum): errors.append(f"Hum inconsistency: DLHM ({dlhm}) > HUM ({hum})")
    if not (hum <= dhhm): errors.append(f"Hum inconsistency: HUM ({hum}) > DHHM ({dhhm})")
    if not (dhhm <= mhhm): errors.append(f"Hum inconsistency: DHHM ({dhhm}) > MHHM ({mhhm})")
    if not (mhhm <= yhhm): errors.append(f"Hum inconsistency: MHHM ({mhhm}) > YHHM ({yhhm})")

    # Pressure hierarchy: YLBR <= MLBR <= DLBR <= BAR <= DHBR <= MHBR <= YHBR
    if not (ylbr <= mlbr): errors.append(f"Bar inconsistency: YLBR ({ylbr}) > MLBR ({mlbr})")
    if not (mlbr <= dlbr): errors.append(f"Bar inconsistency: MLBR ({mlbr}) > DLBR ({dlbr})")
    if not (dlbr <= bar): errors.append(f"Bar inconsistency: DLBR ({dlbr}) > BAR ({bar})")
    if not (bar <= dhbr): errors.append(f"Bar inconsistency: BAR ({bar}) > DHBR ({dhbr})")
    if not (dhbr <= mhbr): errors.append(f"Bar inconsistency: DHBR ({dhbr}) > MHBR ({mhbr})")
    if not (mhbr <= yhbr): errors.append(f"Bar inconsistency: MHBR ({mhbr}) > YHBR ({yhbr})")

    # Wind and Gust hierarchy: WND <= DGST <= MGST <= YGST
    if not (wnd <= dgst): errors.append(f"Wind inconsistency: WND ({wnd}) > DGST ({dgst})")
    if not (dgst <= mgst): errors.append(f"Gust inconsistency: DGST ({dgst}) > MGST ({mgst})")
    if not (mgst <= ygst): errors.append(f"Gust inconsistency: MGST ({mgst}) > YGST ({ygst})")

    # Precipitation hierarchy: DPCP <= MPCP <= YPCP
    if not (dpcp <= mpcp): errors.append(f"Rain inconsistency: DPCP ({dpcp}) > MPCP ({mpcp})")
    if not (mpcp <= ypcp): errors.append(f"Rain inconsistency: MPCP ({mpcp}) > YPCP ({ypcp})")

    # Solar and UV hierarchy
    if not (sun <= dsun): errors.append(f"Solar inconsistency: SUN ({sun}) > DSUN ({dsun})")
    if not (dsun <= msun): errors.append(f"Solar inconsistency: DSUN ({dsun}) > MSUN ({msun})")
    if not (msun <= ysun): errors.append(f"Solar inconsistency: MSUN ({msun}) > YSUN ({ysun})")
    if not (uvi <= dhuv): errors.append(f"UV inconsistency: UVI ({uvi}) > DHUV ({dhuv})")
    if not (dhuv <= mhuv): errors.append(f"UV inconsistency: DHUV ({dhuv}) > MHUV ({mhuv})")
    if not (mhuv <= yhuv): errors.append(f"UV inconsistency: MHUV ({mhuv}) > YHUV ({yhuv})")

    # Nighttime Solar Safeguard: If sun below horizon, SUN and UVI must be exactly 0.0
    solar_elev = get_solar_elevation()
    if solar_elev <= 0.0:
        if sun != 0.0:
            errors.append(f"Nighttime violation: Sun is below horizon ({solar_elev:.1f}°) but SUN={sun} W/m² (must be 0.0)")
        if uvi != 0.0:
            errors.append(f"Nighttime violation: Sun is below horizon ({solar_elev:.1f}°) but UVI={uvi} (must be 0.0)")

    if errors:
        print(f"❌ METEOCLIMATIC VALIDATION FAILED WITH {len(errors)} ERROR(S):")
        for err in errors:
            print(f"   - {err}")
        return False

    print("✅ METEOCLIMATIC QA VALIDATION: ALL PROTOCOL & PHYSICAL CHECKS PASSED!")
    print(f"   Station: {data.get('COD')} | Last Updated: {data.get('UPD')}")
    print(f"   Temperature: Current={tmp:.1f}°C | Today=[{dltm:.1f}, {dhtm:.1f}] | Month=[{mltm:.1f}, {mhtm:.1f}] | Year=[{yltm:.1f}, {yhtm:.1f}]")
    print(f"   Humidity:    Current={hum}% | Today=[{dlhm}, {dhhm}] | Month=[{mlhm}, {mhhm}] | Year=[{ylhm}, {yhhm}]")
    print(f"   Pressure:    Current={bar:.1f}hPa | Today=[{dlbr:.1f}, {dhbr:.1f}] | Month=[{mlbr:.1f}, {mhbr:.1f}] | Year=[{ylbr:.1f}, {yhbr:.1f}]")
    print(f"   Wind/Gust:   Current={wnd:.1f}km/h | Today Gust={dgst:.1f} | Month Gust={mgst:.1f} | Year Gust={ygst:.1f}")
    print(f"   Solar/UV:    Current={sun:.1f}W/m² (elev={solar_elev:.1f}°), UV={uvi:.1f} | Month Sun={msun:.1f}, UV={mhuv:.1f}")
    print(f"   Rain:        Today={dpcp:.1f}mm | Month={mpcp:.1f}mm | Year={ypcp:.1f}mm")
    return True

if __name__ == '__main__':
    target = sys.argv[1] if len(sys.argv) > 1 else TARGET_FILE
    ok = validate_meteoclimatic(target)
    sys.exit(0 if ok else 1)

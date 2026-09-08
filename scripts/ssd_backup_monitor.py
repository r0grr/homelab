#!/usr/bin/env python3
import subprocess
import json
import os
import sys
import time

OUTPUT_JSON = "/opt/servidor/homeassistant/config/ssd_backup_stats.json"

def get_stats():
    stats = {
        "temperature": 0,
        "life_percent": 100,
        "health": "Unknown",
        "power_on_hours": 0,
        "disk_total_gb": 0.0,
        "disk_used_gb": 0.0,
        "disk_free_gb": 0.0,
        "disk_used_percent": 0.0,
        "device": "/dev/sda",
        "model": "Kingston SA400 120GB",
        "mountpoint": "/mnt/backups"
    }

    # 1. SMART data from /dev/sda
    try:
        res = subprocess.run(["smartctl", "-A", "-H", "-j", "/dev/sda"], capture_output=True, text=True, check=False)
        if res.returncode in (0, 4) and res.stdout:
            data = json.loads(res.stdout)
            
            # Temperature
            if "temperature" in data and "current" in data["temperature"]:
                stats["temperature"] = int(data["temperature"]["current"])
            
            # Overall health
            if "smart_status" in data and "passed" in data["smart_status"]:
                stats["health"] = "Bona (OK)" if data["smart_status"]["passed"] else "Alerta"

            # Power on hours
            if "power_on_time" in data and "hours" in data["power_on_time"]:
                stats["power_on_hours"] = int(data["power_on_time"]["hours"])

            # Attributes (SSD Life Left)
            table = data.get("ata_smart_attributes", {}).get("table", [])
            for attr in table:
                if attr.get("id") == 231:
                    stats["life_percent"] = int(attr.get("raw", {}).get("value", 100))
                elif attr.get("id") == 194 and stats["temperature"] == 0:
                    stats["temperature"] = int(attr.get("raw", {}).get("value", 0))
    except Exception as e:
        stats["error_smart"] = str(e)

    # 2. Disk Space for /mnt/backups
    try:
        if os.path.ismount("/mnt/backups"):
            st = os.statvfs("/mnt/backups")
            total = (st.f_blocks * st.f_frsize) / (1024 ** 3)
            free = (st.f_bavail * st.f_frsize) / (1024 ** 3)
            used = total - free
            used_pct = (used / total) * 100 if total > 0 else 0.0
            
            stats["disk_total_gb"] = round(total, 1)
            stats["disk_used_gb"] = round(used, 1)
            stats["disk_free_gb"] = round(free, 1)
            stats["disk_used_percent"] = round(used_pct, 1)
    except Exception as e:
        stats["error_disk"] = str(e)

    return stats

def run_once():
    stats = get_stats()
    tmp_path = OUTPUT_JSON + ".tmp"
    with open(tmp_path, "w") as f:
        json.dump(stats, f, indent=2)
    os.replace(tmp_path, OUTPUT_JSON)

def main():
    while True:
        try:
            run_once()
        except Exception as e:
            print(f"Error in ssd_backup_monitor: {e}", file=sys.stderr)
        time.sleep(60) # Update once every 60 seconds (prevents CPU spin)

if __name__ == "__main__":
    if len(sys.argv) > 1 and sys.argv[1] == "--once":
        run_once()
    else:
        main()

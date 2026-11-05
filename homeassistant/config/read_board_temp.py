#!/usr/bin/env python3
import glob
import json
import os

def find_hwmon_temp(target_name):
    """Cerca de forma robusta la temperatura del sensor hwmon pel seu nom (coretemp, nvme, etc.)."""
    for path in glob.glob("/sys/class/hwmon/hwmon*"):
        try:
            name_file = os.path.join(path, "name")
            if os.path.exists(name_file):
                with open(name_file, "r") as f:
                    name = f.read().strip()
                if name == target_name:
                    temp_file = os.path.join(path, "temp1_input")
                    if os.path.exists(temp_file):
                        with open(temp_file, "r") as tf:
                            return float(tf.read().strip()) / 1000.0
        except Exception:
            continue
    return None

def main():
    try:
        cpu = find_hwmon_temp("coretemp")
        nvme = find_hwmon_temp("nvme")

        sata = None
        sata_file = "/config/ssd_backup_stats.json"
        if os.path.exists(sata_file):
            try:
                with open(sata_file, "r") as sf:
                    sdata = json.load(sf)
                    if "temperature" in sdata and isinstance(sdata["temperature"], (int, float)):
                        sata = float(sdata["temperature"])
            except Exception:
                pass

        # Valors de seguretat si no es pot llegir algun sensor
        if cpu is None and nvme is None:
            print(json.dumps({"temp": 45.0, "method": "fallback"}))
            return

        if cpu is None:
            cpu = nvme
        if nvme is None:
            nvme = cpu

        # Model tèrmic per a Mini PC fanless (dissipador passiu integral):
        # En règim estacionari, la temperatura superficial del dissipador i placa base
        # depèn del flux calorífic del bloc CPU (50%), el controlador NVMe (35%)
        # i el disc SATA de còpies (15%), amb pèrdua convectiva natural a l'aire ambient (-5°C a -7°C).
        if sata is not None:
            est_board = (0.50 * cpu) + (0.35 * nvme) + (0.15 * sata) - 5.0
        else:
            est_board = (0.55 * cpu) + (0.35 * nvme) - 7.0

        est_board = max(20.0, min(85.0, est_board))

        output = {
            "temp": round(est_board, 1),
            "cpu_temp": round(cpu, 1),
            "nvme_temp": round(nvme, 1),
            "sata_temp": round(sata, 1) if sata is not None else None,
            "method": "Model d'equilibri tèrmic passiu (CPU + NVMe + SATA)"
        }
        print(json.dumps(output))

    except Exception as e:
        print(json.dumps({"temp": 45.0, "error": str(e), "method": "error_fallback"}))

if __name__ == "__main__":
    main()

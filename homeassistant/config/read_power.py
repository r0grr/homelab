#!/usr/bin/env python3
import time
import json
import os

CACHE_FILE = "/tmp/rapl_prev.json"
P0 = "/host_powercap/intel-rapl/intel-rapl:0"

def get_energy():
    with open(f"{P0}/energy_uj") as f:
        pkg = int(f.read().strip())
    with open(f"{P0}/intel-rapl:0:0/energy_uj") as f:
        core = int(f.read().strip())
    with open(f"{P0}/intel-rapl:0:1/energy_uj") as f:
        uncore = int(f.read().strip())
    return pkg, core, uncore

def main():
    now = time.time()
    try:
        if not os.path.exists(P0):
            print(json.dumps({"cpu_pkg_w": 0.0, "cpu_core_w": 0.0, "cpu_uncore_w": 0.0, "system_est_w": 0.0}))
            return

        pkg, core, uncore = get_energy()
        pkg_w = core_w = uncore_w = None

        if os.path.exists(CACHE_FILE):
            try:
                with open(CACHE_FILE) as f:
                    prev = json.load(f)
                dt = now - prev.get("time", 0)
                if 1.0 < dt < 120:
                    pkg_w = max(0.0, (pkg - prev["pkg"]) / (dt * 1e6))
                    core_w = max(0.0, (core - prev["core"]) / (dt * 1e6))
                    uncore_w = max(0.0, (uncore - prev["uncore"]) / (dt * 1e6))
            except Exception:
                pass

        if pkg_w is None:
            time.sleep(0.5)
            pkg2, core2, uncore2 = get_energy()
            dt = time.time() - now
            pkg_w = max(0.0, (pkg2 - pkg) / (dt * 1e6))
            core_w = max(0.0, (core2 - core) / (dt * 1e6))
            uncore_w = max(0.0, (uncore2 - uncore) / (dt * 1e6))

        sys_est_w = pkg_w + 5.5  # 5.5W base placa base, 16GB RAM, NVMe SSD i xarxa

        try:
            with open(CACHE_FILE, "w") as f:
                json.dump({"time": now, "pkg": pkg, "core": core, "uncore": uncore}, f)
        except Exception:
            pass

        print(json.dumps({
            "cpu_pkg_w": round(pkg_w, 2),
            "cpu_core_w": round(core_w, 2),
            "cpu_uncore_w": round(uncore_w, 2),
            "system_est_w": round(sys_est_w, 2)
        }))
    except Exception as e:
        print(json.dumps({"error": str(e), "cpu_pkg_w": 0.0, "cpu_core_w": 0.0, "cpu_uncore_w": 0.0, "system_est_w": 0.0}))

if __name__ == "__main__":
    main()

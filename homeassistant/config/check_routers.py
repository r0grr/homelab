#!/usr/bin/env python3
import sys
import subprocess
import json
import os

def ping(host):
    try:
        res = subprocess.run(
            ["ping", "-c", "1", "-W", "1", host],
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL
        )
        return res.returncode == 0
    except Exception:
        return False

target = sys.argv[1] if len(sys.argv) > 1 else ""

if target == "vera":
    is_up = ping("192.168.1.1")
    data = {
        "state": "ON" if is_up else "OFF",
        "model": "Huawei HG8245H (Vera Fibra)",
        "lan_ip": "192.168.1.1",
        "wan_ip": "IP Pública Dinàmica (Vera Fibra)",
        "cgnat_status": "Sense CG-NAT (IP Pública Pròpia)",
        "port_mapping": "Ports 80 & 443 -> 192.168.1.115 (ASUS)"
    }
elif target == "asus_master":
    is_up = ping("192.168.2.1")
    data = {
        "state": "ON" if is_up else "OFF",
        "model": "ASUS ROG Rapture GT-BE18000 (Master)",
        "lan_ip": "192.168.2.1",
        "wan_ip": "192.168.1.115 (Enllaç Vera)",
        "dyndns": os.environ.get("ROUTER_DYNDNS_DOMAIN", "homelab.dyndns.org"),
        "port_forwarding": "Ports 80 & 443 -> 192.168.2.200 (Mini PC)"
    }
elif target == "asus_aimesh":
    is_up = ping("192.168.2.194")
    data = {
        "state": "ON" if is_up else "OFF",
        "model": "ASUS AiMesh Node (Extensor)",
        "role": "Node Satèl·lit AiMesh Mesh",
        "lan_ip": "192.168.2.194",
        "master_ip": "192.168.2.1 (ASUS Master)"
    }
else:
    data = {"state": "OFF", "error": "Unknown target"}

print(json.dumps(data))

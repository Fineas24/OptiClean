#!/usr/bin/env python3

import sys
import time
import subprocess

POMPA_PIN = 18

if len(sys.argv) != 2:
    print("Usage: python3 pompa.py [on|off]")
    sys.exit(1)

command = sys.argv[1].lower()

def gpio_set(pin, value):
    try:
        subprocess.run(
            ["gpioset", "gpiochip0", f"{pin}={value}"],
            check=True
        )
        print(f"GPIO {pin} set to {value}")
    except subprocess.CalledProcessError as e:
        print(f"Error setting GPIO {pin}: {e}")

try:
    if command == "on":
        # Set pin LOW (0) to activate pompa
        gpio_set(POMPA_PIN, 0)
        print("Pompa ON (GPIO 18 set LOW)")
        #time.sleep(30)

    elif command == "off":
        # Set pin HIGH (1) to deactivate pompa
        gpio_set(POMPA_PIN, 1)
        print("Pompa OFF (GPIO 18 set HIGH)")
        #time.sleep(30)

    else:
        print("Invalid command. Use 'on' or 'off'.")

except Exception as e:
    print(f"Error: {e}")

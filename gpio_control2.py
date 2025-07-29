import subprocess
import sys
import time

# Mapare comenzi -> pini GPIO (exemplu)
pin_map = {
    "spray": 18,
    "uscare": 22,
    "start_ultrasunete": 27,
    "start_temp": 23,
    "sus": 22,
    "jos": 24,
    "start_auto": 25,
    "stop_auto": 10
}

if len(sys.argv) < 2:
    print("No command provided")
    sys.exit(1)

command = sys.argv[1]

if command not in pin_map:
    print(f"Invalid command: {command}")
    sys.exit(1)

pin = pin_map[command]

def gpio_set(pin_num, value):
    # value trebuie 0 sau 1
    try:
        subprocess.run(
            ["gpioset", "gpiochip0", f"{pin_num}={value}"],
            check=True
        )
        print(f"Pin {pin_num} set to {value}")
    except subprocess.CalledProcessError as e:
        print("Eroare la setarea pinului:", e)

# Exemplu: activează pinul (1), așteaptă 1 sec, dezactivează (0)
gpio_set(pin, 0)
time.sleep(1)
gpio_set(pin, 1)

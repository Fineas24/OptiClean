import subprocess
import sys

# Mapare comenzi -> pini GPIO
pin_map = {
    "dry": 22,    # uscare
    "pompa": 18,
}

if len(sys.argv) < 2:
    print("No command provided")
    sys.exit(1)

command = sys.argv[1].lower()

if command not in {"dry_on", "dry_off", "pompa_on", "pompa_off"}:
    print(f"Invalid command: {command}")
    sys.exit(1)

# Extragem partea cu pinul (dry/pompa) și ON/OFF
device, action = command.split('_')

pin = pin_map[device]

# Dacă e _on => value=0 (activ), dacă e _off => value=1 (dezactiv)
value = 0 if action == "on" else 1

def gpio_set(pin_num, value):
    try:
        subprocess.run(
            ["gpioset", "gpiochip0", f"{pin_num}={value}"],
            check=True
        )
        print(f"Pin {pin_num} set to {value}")
    except subprocess.CalledProcessError as e:
        print("Eroare la setarea pinului:", e)

gpio_set(pin, value)

from gpiozero import OutputDevice
import sys
import time

pin_map = {
    "spray": 18,
    "uscare": 22,#18
    "start_ultrasunete": 27,
    "start_temp": 23, #22
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

device = OutputDevice(pin)
device.off()   # activează pinul scăzând tensiunea (sau on() în funcție de circuit)
time.sleep(1)
device.on()    # dezactivează pinul

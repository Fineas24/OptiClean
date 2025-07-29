import serial
import sys

if len(sys.argv) < 2:
    print("Usage: python3 send_serial.py <message>")
    sys.exit(1)

message = sys.argv[1]

# Deschide portul serial
ser = serial.Serial('/dev/ttyAMA0', baudrate=115200, timeout=1)

try:
    ser.write((message + "\n").encode('utf-8'))
    print(f"Trimis pe serial: {message}")
finally:
    ser.close()

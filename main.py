import time
import threading
import requests  # not needed for temp now, but you can keep it
import os
import sys
import serial
import mysql.connector
from mysql.connector import Error
from dotenv import load_dotenv
from evdev import InputDevice, categorize, ecodes
import glob  # for sensor file search

# Load environment variables
load_dotenv('.env')

# Serial port for Nextion display
ser = serial.Serial('/dev/ttyUSB0', 9600, timeout=1)

# Barcode device path (adjust if necessary)
DEVICE_PATH = '/dev/input/event5'

# === 1-Wire Sensor Initialization ===
os.system('modprobe w1-gpio')
os.system('modprobe w1-therm')

base_dir = '/sys/bus/w1/devices/'
device_folders = glob.glob(base_dir + '28*')

if not device_folders:
    print("❌ No DS18B20 sensor found!")
    device_file = None
else:
    device_file = device_folders[0] + '/w1_slave'

def read_temp_raw():
    if not device_file or not os.path.exists(device_file):
        return None
    with open(device_file, 'r') as f:
        return f.readlines()

def read_temp():
    lines = read_temp_raw()
    if not lines:
        return None
    # wait for sensor ready
    attempts = 5
    while lines[0].strip()[-3:] != 'YES' and attempts > 0:
        time.sleep(0.2)
        lines = read_temp_raw()
        attempts -= 1
    if lines[0].strip()[-3:] != 'YES':
        return None
    temp_pos = lines[1].find('t=')
    if temp_pos != -1:
        temp_string = lines[1][temp_pos + 2:]
        temp_c = float(temp_string) / 1000.0
        return temp_c
    return None

# === MySQL Connection ===
def create_connection():
    try:
        connection = mysql.connector.connect(
            host=os.getenv('DB_HOST'),
            user=os.getenv('DB_USER'),
            password=os.getenv('DB_PASS'),
            database=os.getenv('DB_NAME'),
            charset='utf8'
        )
        if connection.is_connected():
            print("✅ Connected to MySQL")
            return connection
    except Error as e:
        print(f"❌ MySQL error: {e}")
    return None

def check_active_preset_loop(conn):
    while True:
        try:
            cursor = conn.cursor(dictionary=True)
            cursor.execute("SELECT * FROM active_preset LIMIT 1")
            result = cursor.fetchone()
            if result:
                print("🔁 Active preset:", result)
            else:
                print("🔁 No active preset")
            cursor.close()
        except Error as e:
            print(f"MySQL read error: {e}")
        time.sleep(2)

# === Nextion Serial Communication ===
def send_command(cmd):
    ser.write(cmd.encode('utf-8'))
    ser.write(b'\xFF\xFF\xFF')

def read_nextion_response():
    buffer = bytearray()
    while True:
        b = ser.read()
        if b:
            buffer += b
            if buffer[-3:] == b'\xFF\xFF\xFF':
                break
    try:
        return buffer[:-3].decode('utf-8', errors='ignore').strip()
    except:
        return ""

# === Handlers for Start and Stop buttons ===
# ... (rest of your imports and code unchanged)

def handle_start():
    print("▶️ Start button was pressed (handled by handle_start)")

def handle_stop():
    print("⏹ Stop button was pressed (handled by handle_stop)")

def nextion_loop():
    send_command('t0.txt="Waiting for button"')
    while True:
        if ser.in_waiting:
            response = read_nextion_response()
            if response == "pStart":   # changed from "start"
                handle_start()
                send_command('t0.txt="Started"')
            elif response == "pstop": # changed from "stop"
                handle_stop()
                send_command('t0.txt="Stopped"')

# ... (rest of your code unchanged)

# === Temperature loop reading local sensor instead of API ===
def temperature_loop():
    while True:
        temp = read_temp()
        if temp is None:
            print("❌ Sensor read failed")
            send_command('t2.txt="Sensor error"')
        else:
            print(f"🌡 Temperature: {temp:.2f} °C")
            send_command(f't2.txt="temp: {temp:.2f}°C"')
        time.sleep(2)

# === Barcode Reader ===
def barcode_loop():
    try:
        device = InputDevice(DEVICE_PATH)
        print(f"🧾 Listening to barcode reader: {device.name}")
    except FileNotFoundError:
        print(f"❌ Barcode device {DEVICE_PATH} not found.")
        return

    barcode = ''
    shift = False
    key_map = {
        2: '1', 3: '2', 4: '3', 5: '4', 6: '5', 7: '6', 8: '7', 9: '8',
        10: '9', 11: '0', 12: '-', 13: '=', 16: 'q', 17: 'w', 18: 'e',
        19: 'r', 20: 't', 21: 'y', 22: 'u', 23: 'i', 24: 'o', 25: 'p',
        26: '[', 27: ']', 30: 'a', 31: 's', 32: 'd', 33: 'f', 34: 'g',
        35: 'h', 36: 'j', 37: 'k', 38: 'l', 39: ';', 40: '\n', 44: 'z',
        45: 'x', 46: 'c', 47: 'v', 48: 'b', 49: 'n', 50: 'm', 51: ',',
        52: '.', 53: '/', 28: '\n'
    }

    def insert_presets_for_barcode(barcode):
        try:
            conn = create_connection()
            if not conn:
                print("❌ DB connection error during preset insert.")
                return
            cursor = conn.cursor(dictionary=True)

            cursor.execute("SELECT name, use_order, type, time_times FROM barcode_presets WHERE barcode = %s", (barcode,))
            presets = cursor.fetchall()

            if not presets:
                print(f"⚠️ No presets found for barcode {barcode}")
                return

            cursor.execute("SELECT id FROM users WHERE active = 1 LIMIT 1")
            user = cursor.fetchone()
            if not user:
                print("⚠️ No active user found.")
                return

            owner_id = user['id']

            cursor.execute("SELECT MAX(preset_id) as max_id FROM presets")
            result = cursor.fetchone()
            next_preset_id = (result['max_id'] or 0) + 1

            for p in presets:
                cursor.execute("""
                    INSERT INTO presets (preset_id, name, use_order, owner_id, type, time_times)
                    VALUES (%s, %s, %s, %s, %s, %s)
                """, (next_preset_id, p['name'], p['use_order'], owner_id, p['type'], p['time_times']))

            conn.commit()
            print(f"✅ Inserted {len(presets)} presets for barcode {barcode} and user {owner_id}")

        except Error as e:
            print(f"❌ DB error during preset insert: {e}")
        finally:
            if conn.is_connected():
                conn.close()

    for event in device.read_loop():
        if event.type == ecodes.EV_KEY:
            key_event = categorize(event)
            if key_event.keystate == key_event.key_down:
                if key_event.scancode == ecodes.KEY_LEFTSHIFT:
                    shift = True
                elif key_event.scancode in [ecodes.KEY_ENTER, 28]:
                    if barcode.strip():
                        print("✅ Barcode scanned:", barcode)
                        insert_presets_for_barcode(barcode.strip())
                    barcode = ''
                else:
                    key = key_map.get(key_event.scancode, '')
                    if shift:
                        key = key.upper()
                    barcode += key
            elif key_event.keystate == key_event.key_up:
                if key_event.scancode == ecodes.KEY_LEFTSHIFT:
                    shift = False


# === Main ===
def main():
    conn = create_connection()
    if not conn:
        print("Exiting due to MySQL failure.")
        return

    threads = [
        threading.Thread(target=check_active_preset_loop, args=(conn,)),
        threading.Thread(target=nextion_loop),
        threading.Thread(target=temperature_loop),
        threading.Thread(target=barcode_loop)
    ]

    for t in threads:
        t.daemon = True
        t.start()

    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        print("\n👋 Stopping script...")
        if conn.is_connected():
            conn.close()
        sys.exit(0)

if __name__ == '__main__':
    main()

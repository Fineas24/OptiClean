import serial
import time
import subprocess

# Comenzi permise
valid_commands = {
    "spray", "uscare", "start_ultrasunete", "start_temp",
    "sus", "jos", "start_auto", "stop_auto"
}

# Deschide UART pe Raspberry Pi
ser = serial.Serial('/dev/ttyAMA0', baudrate=115200, timeout=1)

try:
    while True:
        # Trimite un mesaj de stare (opțional)
        ser.write(b'Pi ready...\n')

        # Verifică dacă a sosit ceva
        if ser.in_waiting > 0:
            data = ser.readline().decode('utf-8').strip()
            print(f"Pico a trimis: {data}")

            # Verifică dacă mesajul e o comandă validă
            if data in valid_commands:
                print(f"Execut comanda: {data}")
                try:
                    result = subprocess.run(
                        ["python", "/var/www/html/opticlean/server/gpio_control.py", data],
                        capture_output=True,
                        text=True
                    )
                    print("Rezultat execuție:\n", result.stdout)
                    if result.stderr:
                        print("Eroare:\n", result.stderr)
                except Exception as e:
                    print("Eroare la execuție:", e)
            else:
                print("Comandă necunoscută sau nevalidă:", data)

        time.sleep(1)

except KeyboardInterrupt:
    ser.close()
    print("Conexiune UART închisă")

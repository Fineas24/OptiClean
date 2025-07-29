#!/usr/bin/env python3
from flask import Flask, jsonify
import glob
import time

app = Flask(__name__)

def read_temp():
    base_dir = '/sys/bus/w1/devices/'
    try:
        device_folder = glob.glob(base_dir + '28*')[0]
        device_file = device_folder + '/w1_slave'

        with open(device_file, 'r') as f:
            lines = f.readlines()

        while lines[0].strip()[-3:] != 'YES':
            time.sleep(0.2)
            with open(device_file, 'r') as f:
                lines = f.readlines()

        equals_pos = lines[1].find('t=')
        if equals_pos != -1:
            temp_string = lines[1][equals_pos+2:]
            temp_c = float(temp_string) / 1000.0
            return temp_c
    except:
        return None

@app.route("/get_temp")
def get_temp():
    temp = read_temp()
    return jsonify(temperature=temp)

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=8000)

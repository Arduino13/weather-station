import math
from gpiozero import Button
from gpiozero import MCP3008
import statistics
import threading
import bme280
import smbus2
import influx as SQL
import time

'''
Script for saving meassured values to database
'''
radius = 0.09
circumstance = math.pi * 2 * radius
bucket_size = 0.2794
address = 0x76 #may differ
wind_array = []
angles_array = []

#map between voltage on voltage divider to wind vane angle
volts_to_wind = {0.4: 0.0,
                 1.4: 22.5,
                 1.2: 45.0,
                 2.8: 67.5,
                 2.7: 90.0,
                 2.9: 112.5,
                 2.2: 135.0,
                 2.5: 157.5,
                 1.8: 180.0,
                 2.0: 202.5,
                 0.7: 225.0,
                 0.8: 247.5,
                 0.1: 270.0,
                 0.3: 292.5,
                 0.2: 315.0,
                 0.6: 337.5}

wind_count = 0
rain_count = 0

#called every half revolution of aenometer
def spin():
    global wind_count
    wind_count = wind_count + 1

#called when rain bucket  
def rain():
    global rain_count
    rain_count = rain_count + 1

#saves wind vane angle every five seconds to an array that is than averaged when saving
def change_wind_direction(): 
    global volts_to_wind
    global angles_array
    global direction
    
    value = round(direction.value*3.3,1)
    if value in volts_to_wind:
        angles_array.append(volts_to_wind[value])

    threading.Timer(5,change_wind_direction).start()

def get_average(angles): #averaging wind vane angles
    sin_sum = 0.0
    cos_sum = 0.0

    for angle in angles:
        r = math.radians(angle)
        sin_sum += math.sin(r)
        cos_sum += math.cos(r)

    flen = float(len(angles))
    s = sin_sum / flen
    c = cos_sum / flen
    arc = math.degrees(math.atan(s / c))
    average = 0.0

    if s > 0 and c > 0:
        average = arc
    elif c < 0:
        average = arc + 180
    elif s < 0 and c > 0:
        average = arc + 360

    return 0.0 if average == 360 else average

def save_wind_speed():
    global wind_count
    global wind_array

    count = wind_count/2 #every half rotation switch is closed
    wind_count = 0
    speed = ((count*circumstance)/60)*1.18 #1.18 is aenometr factor
    wind_array.append(speed) #to know wind gusts and for better average of wind speed
    while len(wind_array)>5: 
        wind_array.pop(0)
        
    if len(wind_array) != 0:
        avergage_speed = statistics.mean(wind_array)
        max_speed = max(wind_array)
        SQL.sql_save_wind(round(max_speed,2),round(avergage_speed,2))
    
def save_rain():
    global rain_count

    rain = rain_count*bucket_size
    rain_count = 0
    SQL.sql_save_rain(round(rain,2))
    
def save_wind_direction(): 
    global angles_array
    
    while len(angles_array)>12:
        angles_array.pop(0)
    average = get_average(angles_array)

    if len(angles_array) != 0:
        SQL.sql_save_wind_d(round(average,2))

#meassures value from temperature sensor outside housing
def get_ground_temp():
    global ground_temp

    voltage = ground_temp.value*3.3
    resistance = (3.3*10000)-(voltage*10000)
    resistance /= voltage

    #voltage divider with 10k resistor
    temp = resistance/10000.0
    temp = math.log(temp)
    temp = temp/3950.0
    temp += 1.0 / (25+273.15)
    temp = 1.0 / temp
    temp -= 273.15
    temp = round(temp,2)   

    return temp

def save_tchp():
    global bus
    global address
    temp_c=0

    for i in range(0,5): 
        #meassurements turn out to be quite noisy so i repeat meassurement few times in row
        temp_c += get_ground_temp()
        time.sleep(0.1)
    temp = temp_c/5

    bme280_data = bme280.sample(bus,address)
    humidity  = bme280_data.humidity
    pressure  = bme280_data.pressure
    station_temperature = bme280_data.temperature

    SQL.sql_save_thp(round(ambient_temperature,2),round(temp,2),round(humidity,2),round(pressure,2))

def save_to_DB():
    threading.Timer(60, save_to_DB).start()

    save_wind_direction()
    save_wind_speed()
    save_rain()
    save_tchp()

wind_speed = Button(5)
wind_speed.when_pressed = spin

rain_sens = Button(6)
rain_sens.when_pressed = rain

direction = MCP3008(channel=0)
ground_temp = MCP3008(channel=7)

bus = smbus2.SMBus(1)
bme280.load_calibration_params(bus,address)

threading.Timer(60, save_to_DB).start()
threading.Timer(5, change_wind_direction).start()

print("running")

from datetime import datetime
import influxdb_client
from influxdb_client.client.write_api import SYNCHRONOUS

unix_time = datetime.now().timestamp()*10**9 #influxdb works with nanoseconds
db_name = "weather"
org=""
token=""

def open_db():
    global unix_time
    db = influxdb_client.InfluxDBClient(url="http://127.0.0.1:8086")
    write_api = db.write_api(write_options=SYNCHRONOUS)
    date=datetime.now().timestamp()*10**9
    return write_api

def sql_save_wind(Max,average):
    db = open_db()
    p = influxdb_client.Point('wind_tab_speed').field("time",unix_time).field("max",Max).field("average",average)
    db.write(bucket=db_name, org=org, record=p)

def sql_save_thp(temp,temp_g,hum,press):
    db = open_db()
    p = influxdb_client.Point('tchp_tab').field("time", unix_time).field("temp",temp).field("temp_g",temp_g).field("hum",hum).field("press",press)
    db.write(bucket=db_name, org=org, record=p)
    
def sql_save_wind_d(angle):
    db = open_db()
    p = influxdb_client.Point('wind_tab_direction').field("time", unix_time).field("angle", angle)
    db.write(bucket=db_name, org=org, record=p)

def sql_save_rain(rain):
    db = open_db()
    p = influxdb_client.Point('rain_tab').field("time", unix_time).field("rain", rain);
    db.write(bucket=db_name, org=org, record=p)

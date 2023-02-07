# Weather station
Weather station based on Raspberry Pi Zero
![20201115_142930](https://user-images.githubusercontent.com/26491801/217270195-4b792de5-d806-42df-a673-339b69042fa5.jpg)

# Hardware 
1. Raspberry Pi Zero
2. Wind and rain sensor assembly [datasheet](https://www.argentdata.com/files/80422_datasheet.pdf), you can buy for example from aliexpress 
3. MCP3008 analog to digital converter 
4. BME280 combined humidity and pressure sensor
5. NTC termistor, i used one that is used for measuring water temperature, 10K with temperature coeficient 3380K,
unfortunetly i could not find documentation for it in english. [datasheet](https://dratek.cz/docs/produkty/0/115/1488979094.pdf)
6. Two 10k resistors

I connect everything together as shown below

![Screenshot from 2023-02-07 15-25-17](https://user-images.githubusercontent.com/26491801/217271648-df82b3f4-ca0e-4f2a-9514-2844f81b2608.png)

![schemeit-project](https://user-images.githubusercontent.com/26491801/217270361-e28a7f99-0c94-4ef4-b899-fc54d5145316.png)

There are two voltage dividers one for NTC termistor and another one for wind vane. 

# Software 
For storing meassured data i use InfluxDB database(1.8), it's advantage is that even with large amout of data it works really well even on Raspberry Pi Zero however i recommend to run database on external server for better data backup 
and performance.

Web interface is written in PHP with JpGraph(4.2.9) library. It's divided into two sections, overview with current
meassurements and option to view detailed history of one day, and history to display chosen time seciton.

![Screenshot from 2023-02-07 15-56-26](https://user-images.githubusercontent.com/26491801/217281193-8a8fd8f4-a827-4d37-bef2-bb8c46bcc29a.png)
![Screenshot from 2023-02-07 15-58-45](https://user-images.githubusercontent.com/26491801/217281212-51391736-d528-4af4-8cb8-ebe135ef9e6b.png)
![Screenshot from 2023-02-07 15-59-39](https://user-images.githubusercontent.com/26491801/217281232-8099fe81-f7c6-4719-88ed-12092a8c7585.png)

# Current status
Weather station is been now working for almost four years without major problems, i only found that humidity sensor on 
BME280 stuck on 100% humidity after some time in my case after around two years. Now i am trying to get better values
from it by varying calibration parameters. 

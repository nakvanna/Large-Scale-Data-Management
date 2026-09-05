# Start Webservice
```cmd
C:\xampp\apache2\bin\httpd.exe -f C:\xampp\apache2\conf\httpd.conf
```
```cmd
C:\xampp\apache\bin\httpd.exe -f C:\xampp\apache\conf\httpd.conf
```


# Start DB Service
## Go to folder bin
```cmd
cd C:\xampp\mysql\bin
```

# Start Defualt DB 3306
```cmd
mysqld --defaults-file=C:\xampp\mysql\bin\my.ini --console
```

# Start Our Custom DB 3307 or Others
```cmd
mysqld --defaults-file=C:\xampp\mysql\my_node2.ini --console
```
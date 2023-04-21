## Exchange rates API

This is an API for recording daily currency exchange rates (EUR, USD, GBP) into a database and retrieving them from the database. The application is built using PHP and Laravel framework.

Access to endpoints is secured by authorization and role for API. There are endpoints that allow users to add and download exchange rates from the database, as well as log in, log out, refresh their token and change their password. Users with administrator privileges also have access to additional endpoints that allow editing and deleting existing exchange rate records, as well as adding, editing and deleting users.
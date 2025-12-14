Quick Start
Start the Environment:
docker-compose up -d --build
Install Dependencies: You need to install the PHP dependencies (dompdf) using Composer. If you have Composer installed locally:
composer install
Or run it via a temporary docker container:
docker run --rm -v $(pwd):/app -w /app composer install
Access the Site:
Public Home: http://localhost:8080/index.php
Admin Dashboard: http://localhost:8080/dashboard.php
Credentials:
Username: admin
Password: password


I have the concern that although the menu now fits perfectly on two pages, the eventual owner of the site might be frustrated if they end up adding more items or removing items as they will have no way to make them fit on the two pages evenly without changing the menu page sizes. To account for this, could you implement a system that will adjust the zoom (size of everything within the page) upon pdf generation so that it will always fit the two pages no matter if there are more or less menu items and specials that there are currently. They would have to deal with things getting cramped, but that is just the physical limmitation. They can always change the page size too if they really need to.

please update the database seed files. start by pulling the database from docker and reference it to update the seeds as the data on the docker database is now the correct version and some of the fields/structure/data in the seed files is now outdated. since you are bringing them up to date with what is currently on the dev server database, there is no need to reseed them. just update them so when I deploy, I will have the same database data as I do now on the dev docker server.

I would like the location text just under the header to link to the user's default map app if possible. On PC, it would be nice it it opened the location on google maps in a new tab if that's possible.

Please remove the "download pdf" button from the public specials page.
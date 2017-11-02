# bingo.php

The sample is ...

- using ajax
- implement a bingo game algorithm
- with sql(sqlite3)

... for my students impromptu.

note: a part of sources is helpful for you(my students) maybe, but currently these source codes are not beautiful and the design is no good.

todo: fix software design and refactoring source code.

## Important Notice

This code written in 2013. PHP was 5.x at the time. And, Google was servicing the OpenID authentication system. But, in now ( this section is wrote in 2017-11 ), the latest PHP is 7.x and Google was stop the OpenID service.

If you need to run the app then you need replace the authentication code from the OpenID based implements to something else.

BTW, I added the section because I got a mail. Ze saild "How to install?", "Where is install file?" and "Where is SQL?".

- The answer of "How to install?" and "Where is install file?":
    1. The repos can run on PHP-7.0.22 and a few php extensional plugins. If you use Ubuntu-16.04 then ...
        1. `apt install php`
        2. `apt install php-xml`; If you lack it then you occur an error `Fatal error: Call to undefined function: simplexml_load_file()` in PHP log
        3. `apt install php-sqlite3`; If you lack it then you occur an error `... main ERROR ... exception-message: could not find driver` in log/main.log
        4. `php -S 127.0.0.1:10080` in a copy of this repos and access it with web browser, then you see the page of `Bingo!`.
        5. You can command with JSON something like `http://127.0.0.1:10080/main.php?c={"c":"main"}` .
            - Note: But unfortunately, you can't run the app normaly because authentication system will fail. That's because Google's OpenID service has been suspended. You need replace an authentication implementation if you need run the app normaly.
- The answer of "Where is SQL?"
    1. This app are use the SQLite3 with the PDO. You don't need a SQL server and a SQL settings.

I haven't a cost and resources for maintain this app. I'm not a teacher already since 5 years ago. And, I trashed the documents and textbooks of the lecture included this app already. Therefore, the update is the last update for this repos, maybe. Good luck, thank you :)

## License

### included libraries

- [log4php](http://logging.apache.org/log4php/)
- [Smarty](http://www.smarty.net/)
- [LightOpenID](http://gitorious.org/lightopenid)

### included tool

- [sqlite manager](http://www.sqlitemanager.org/)

### the other original sources

&copy; 2013 Wonder Rabbit Project / Usagi Ito. License is CC0.

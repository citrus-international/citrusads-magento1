###ADDING A NEW MAGENTO CRON TASK VIA SSH/TERMINAL

Run:

```crontab -e ```

At this point you will see a text editor, where you can add or edit cron tasks. Starting from a new line, add the following record:

`````*/5 * * * * sh /path/to/your/magento/site/root/cron.sh`````

This allow the cronjob to run every 5 minutes.

Save the changes and close the file.

If you did everything correctly, run

```crontab -l ```

This command will show you the newly created task.

If required, you need to start/restart you cron service

```service cron restart```

If on Ubuntu, you might need to edit this file 

`/etc/crontab`.


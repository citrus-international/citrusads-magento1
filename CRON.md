###ADDING A NEW MAGENTO CRON TASK VIA SSH/TERMINAL

Run:

```crontab -e ```

At this point you will see a text editor where you can add or edit cron tasks. Starting from a new line, add the following record:

`````*/5 * * * * sh /path/to/your/magento/site/root/cron.sh`````

This allows the cronjob to run every 5 minutes.

Save the changes and close the file.

Assuming that you did everything correctly, running

```crontab -l ```

will show you the newly created task.

If required, you can start or restart your cron service with the following command:

```service cron restart```

If you're using Ubuntu, you might need to edit this file:

`/etc/crontab`.

